<?php
namespace ZBateson\MailMimeParser\Header\Part;

use PHPUnit_Framework_TestCase;

/**
 * Description of MimeLiteralTest
 *
 * @group HeaderParts
 * @group MimeLiteralPart
 * @covers ZBateson\MailMimeParser\Header\Part\MimeLiteralPart
 * @covers ZBateson\MailMimeParser\Header\Part\HeaderPart
 * @author Zaahid Bateson
 */
class MimeLiteralPartTest extends PHPUnit_Framework_TestCase
{
    private $charsetConverter;
    
    public function setUp()
    {
        $this->charsetConverter = $this->getMock('ZBateson\StreamDecorators\Util\CharsetConverter');
    }
    
    protected function assertDecoded($expected, $encodedActual)
    {
        $part = new MimeLiteralPart($this->charsetConverter, $encodedActual);
        $this->assertEquals($expected, $part->getValue());
        return $part;
    }
    
    public function testBasicValue()
    {
        $this->charsetConverter->expects($this->never())
            ->method('convert');
        $this->assertDecoded('Step', 'Step');
    }
    
    public function testNullLanguage()
    {
        $this->charsetConverter->expects($this->never())
            ->method('convert');
        $part = $this->assertDecoded('Step', 'Step');
        $this->assertEquals([
            [ 'lang' => null, 'value' => 'Step' ]
        ], $part->getLanguageArray());
    }
    
    public function testMimeEncoding()
    {
        $this->charsetConverter->expects($this->once())
            ->method('convert')
            ->with('Kilgore Trout', 'US-ASCII', 'UTF-8')
            ->willReturn('Kilgore Trout');
        $this->assertDecoded('Kilgore Trout', '=?US-ASCII?Q?Kilgore_Trout?=');
    }
    
    public function testMimeEncodingNullLanguage()
    {
        $this->charsetConverter->expects($this->once())
            ->method('convert')
            ->with('Kilgore Trout', 'US-ASCII', 'UTF-8')
            ->willReturn('Kilgore Trout');
        $part = $this->assertDecoded('Kilgore Trout', '=?US-ASCII?Q?Kilgore_Trout?=');
        $this->assertEquals([
            [ 'lang' => null, 'value' => 'Kilgore Trout' ]
        ], $part->getLanguageArray());
    }
    
    public function testEncodingTwoParts()
    {
        $kilgore = '=?US-ASCII?Q?Kilgore_Trout?=';
        $snow = '=?US-ASCII?Q?Jon_Snow?=';
        
        $this->charsetConverter->expects($this->exactly(7))
            ->method('convert')
            ->withConsecutive(
                ['Kilgore Trout', 'US-ASCII', 'UTF-8'],
                ['Jon Snow', 'US-ASCII', 'UTF-8'],
                ['Kilgore Trout', 'US-ASCII', 'UTF-8'],
                ['Jon Snow', 'US-ASCII', 'UTF-8'],
                ['Kilgore Trout', 'US-ASCII', 'UTF-8'],
                ['Jon Snow', 'US-ASCII', 'UTF-8'],
                ['Jon Snow', 'US-ASCII', 'UTF-8']
            )
            ->willReturnOnConsecutiveCalls(
                'Kilgore Trout',
                'Jon Snow',
                'Kilgore Trout',
                'Jon Snow',
                'Kilgore Trout',
                'Jon Snow',
                'Jon Snow'
            );
        
        $this->assertDecoded(
            ' Kilgore TroutJon Snow ',
            " $kilgore   $snow "
        );
        $this->assertDecoded(
            'Kilgore TroutJon Snow',
            "{$kilgore}{$snow}"
        );
        $this->assertDecoded(
            'Kilgore Trout   Jon',
            "$kilgore   Jon"
        );
        $this->assertDecoded(
            'Kilgore   Jon Snow',
            "Kilgore   $snow"
        );
        $this->assertDecoded(
            'KilgoreJon SnowTrout',
            "Kilgore{$snow}Trout"
        );
    }
    
    public function testNonAscii()
    {
        $this->charsetConverter = $this->getMockBuilder('ZBateson\StreamDecorators\Util\CharsetConverter')
            ->setMethods(['__toString'])
            ->getMock();
        
        $this->assertDecoded(
            'κόσμε fløde',
            '=?UTF-8?B?zrrhvbnPg868zrUgZmzDuGRl?='
        );
        $this->assertDecoded(
            'هلا هلا شخبار؟',
            '=?WINDOWS-1256?B?5eHHIOXhxyDUzsjH0b8=?='
        );
        // either I test with an extra space at the end and they're equal, or
        // the last character is missing and they're not equal - something's
        // wrong with this test ...
        $this->assertDecoded(
            'ידיעת שפה אחת אינה מספיקה ',
            '=?WINDOWS-1255?B?6ePp8vog+fTkIODn+iDg6fDkIO7x9On35CA==?='
        );
        $this->assertDecoded(
            'がんばります',
            '=?ISO-2022-JP?B?GyRCJCwkcyRQJGokXiQ5GyhC?='
        );
        $this->assertDecoded(
            'دنت كبتن والله',
            '=?CP1256?Q?=CF=E4=CA=20=DF=C8=CA=E4=20=E6=C7=E1=E1=E5?='
        );
        $this->assertDecoded(
            'في إيه يا باشا',
            '=?UTF-8?B?2YHZiiDYpdmK2Ycg2YrYpyDYqNin2LTYpw==?='
        );
        $this->assertDecoded(
            '桜',
            '=?ISO-2022-JP?B?GyRCOnkbKEI=?='
        );
        $this->assertDecoded(
            '﻿这也不会,那也不会',
            '=?UTF-32?B?//4AANmPAABfTgAADU4AABpPAAAsAAAAo5AAAF9OAAANTgAAGk8AAA==?='
        );
        $this->assertDecoded(
            'セミオーダー感覚で選ぶ、ジャケット',
            '=?shift_jis?B?g1qDfoNJgVuDX4FbirSKb4LFkUmC1IFBg1eDg4NQg2KDZw==?='
        );
        $this->assertDecoded('el pingüino', 'el pingüino');
        $this->assertDecoded('外為ｵﾝﾗｲﾝﾃﾞﾓ(25)(デモ)決済約定のお知らせ', '=?iso-2022-jp?Q?=1B$B300Y=1B(I5]W2]C^S=1B(B(25?=
            =?iso-2022-jp?Q?)(=1B$B%G%b=1B(B)=1B$B7h:QLsDj$N$*CN$i$;=1B(B?=');
    }
    
    public function testIgnoreSpacesBefore()
    {
        $part = new MimeLiteralPart($this->charsetConverter, '=?US-ASCII?Q?Kilgore_Trout?=Blah');
        $this->assertTrue($part->ignoreSpacesBefore(), 'ignore spaces before');
        $this->assertFalse($part->ignoreSpacesAfter(), 'ignore spaces after');
    }
    
    public function testIgnoreSpacesAfter()
    {
        $part = new MimeLiteralPart($this->charsetConverter, 'Blah=?US-ASCII?Q?Kilgore_Trout?=');
        $this->assertFalse($part->ignoreSpacesBefore(), 'ignore spaces before');
        $this->assertTrue($part->ignoreSpacesAfter(), 'ignore spaces after');
    }
    
    public function testIgnoreSpacesBeforeAndAfter()
    {
        $part = new MimeLiteralPart($this->charsetConverter, '=?US-ASCII?Q?Kilgore_Trout?=');
        $this->assertTrue($part->ignoreSpacesBefore(), 'ignore spaces before');
        $this->assertTrue($part->ignoreSpacesAfter(), 'ignore spaces after');
    }
    
    public function testLanguageParts()
    {
        $this->charsetConverter = $this->getMockBuilder('ZBateson\StreamDecorators\Util\CharsetConverter')
            ->setMethods(['__toString'])
            ->getMock();
        
        $part = $this->assertDecoded(
            'Hello and bonjour mi amici. Welcome!',
            'Hello and =?UTF-8*fr-be?Q?bonjour_?= =?UTF-8*it?Q?mi amici?=. Welcome!'
        );
        $expectedLang = [
            [ 'lang' => null, 'value' => 'Hello and ' ],
            [ 'lang' => 'fr-be', 'value' => 'bonjour ' ],
            [ 'lang' => 'it', 'value' => 'mi amici' ],
            [ 'lang' => null, 'value' => '. Welcome!' ]
        ];
        $this->assertEquals($expectedLang, $part->getLanguageArray());
    }
}
