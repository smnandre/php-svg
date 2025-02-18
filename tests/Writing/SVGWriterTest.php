<?php

namespace SVG\Writing;

/**
 * @covers \SVG\Writing\SVGWriter
 *
 * @SuppressWarnings(PHPMD)
 */
class SVGWriterTest extends \PHPUnit\Framework\TestCase
{
    private $xmlDeclaration = '<?xml version="1.0" encoding="utf-8"?>';

    public function testShouldIncludeXMLDeclaration(): void
    {
        // should start with the XML declaration
        $obj = new SVGWriter();
        $this->assertEquals($this->xmlDeclaration, $obj->getString());
    }

    public function testShouldSupportStandaloneFalse(): void
    {
        // should not prepend the XML declaration
        $obj = new SVGWriter(false);
        $this->assertEquals('', $obj->getString());
    }

    public function testShouldWriteTags(): void
    {
        // should write opening and closing tags for containers
        $obj = new SVGWriter();
        $node = new \SVG\Nodes\Structures\SVGGroup();
        $obj->writeNode($node);
        $expect = $this->xmlDeclaration . '<g />';
        $this->assertEquals($expect, $obj->getString());

        // should write self-closing tag for non-containers
        $obj = new SVGWriter();
        $node = new \SVG\Nodes\Shapes\SVGRect();
        $obj->writeNode($node);
        $expect = $this->xmlDeclaration . '<rect />';
        $this->assertEquals($expect, $obj->getString());
    }

    public function testShouldWriteAttributes(): void
    {
        // should write attributes for containers
        $obj = new SVGWriter();
        $node = new \SVG\Nodes\Structures\SVGGroup();
        $node->setAttribute('id', 'testg');
        $obj->writeNode($node);
        $expect = $this->xmlDeclaration . '<g id="testg" />';
        $this->assertEquals($expect, $obj->getString());
    }

    public function testShouldWriteStyles(): void
    {
        // should serialize styles correctly
        $obj = new SVGWriter();
        $node = new \SVG\Nodes\Structures\SVGGroup();
        $node->setStyle('fill', '#ABC')->setStyle('opacity', '.5');
        $obj->writeNode($node);
        $expect = $this->xmlDeclaration . '<g style="fill: #ABC; opacity: .5" />';
        $this->assertEquals($expect, $obj->getString());
    }

    public function testShouldWriteChildren(): void
    {
        // should write children
        $obj = new SVGWriter();
        $node = new \SVG\Nodes\Structures\SVGGroup();
        $childNode = new \SVG\Nodes\Structures\SVGGroup();
        $svgRect = new \SVG\Nodes\Shapes\SVGRect();
        $childNode->addChild($svgRect);

        $node->addChild($childNode);
        $obj->writeNode($node);
        $expect = $this->xmlDeclaration . '<g><g><rect /></g></g>';
        $this->assertEquals($expect, $obj->getString());
    }

    public function testShouldWriteStyleTagInCDATA(): void
    {
        // should enclose style tag content in <![CDATA[...]]>
        $obj = new SVGWriter();
        $node = new \SVG\Nodes\Structures\SVGStyle('g {display: none;}');
        $obj->writeNode($node);
        $expect = $this->xmlDeclaration . '<style type="text/css">' .
            '<![CDATA[g {display: none;}]]></style>';
        $this->assertEquals($expect, $obj->getString());
    }

    public function testShouldEncodeEntities(): void
    {
        // should encode entities in attributes
        $obj = new SVGWriter();
        $svgGroup = new \SVG\Nodes\Structures\SVGGroup();
        $svgGroup->setAttribute('id', '" foo&bar>')->setStyle('content', '" foo&bar>');
        $obj->writeNode($svgGroup);
        $expect = $this->xmlDeclaration . '<g id="&quot; foo&amp;bar&gt;" ' .
            'style="content: &quot; foo&amp;bar&gt;" />';
        $this->assertEquals($expect, $obj->getString());

        // should encode entities in value
        $obj = new SVGWriter();
        $svgStyle = new \SVG\Nodes\Texts\SVGTitle('" foo&bar>');
        $obj->writeNode($svgStyle);
        $expect = $this->xmlDeclaration . '<title>&quot; foo&amp;bar&gt;</title>';
        $this->assertEquals($expect, $obj->getString());

        // should not encode entities in style body
        $obj = new SVGWriter();
        $svgStyle = new \SVG\Nodes\Structures\SVGStyle('" foo&bar>');
        $obj->writeNode($svgStyle);
        $expect = $this->xmlDeclaration . '<style type="text/css">' .
            '<![CDATA[" foo&bar>]]></style>';
        $this->assertEquals($expect, $obj->getString());
    }

    public function testShouldWriteValue(): void
    {
        // should add value before closing tag
        $obj = new SVGWriter();
        $svgText = new \SVG\Nodes\Texts\SVGText();
        $svgText->setValue('hello world');
        $obj->writeNode($svgText);
        $expect = $this->xmlDeclaration . '<text x="0" y="0">hello world</text>';
        $this->assertEquals($expect, $obj->getString());

        // should escape HTML entities in value
        $obj = new SVGWriter();
        $svgText = new \SVG\Nodes\Texts\SVGText();
        $svgText->setValue('hello& <world>');
        $obj->writeNode($svgText);
        $expect = $this->xmlDeclaration . '<text x="0" y="0">hello&amp; &lt;world&gt;</text>';
        $this->assertEquals($expect, $obj->getString());

        // should add value even for non-containers
        $obj = new SVGWriter();
        $svgRect = new \SVG\Nodes\Shapes\SVGRect();
        $svgRect->setValue('hello world');
        $obj->writeNode($svgRect);
        $expect = $this->xmlDeclaration . '<rect>hello world</rect>';
        $this->assertEquals($expect, $obj->getString());

        // should not add empty value
        $obj = new SVGWriter();
        $svgRect = new \SVG\Nodes\Shapes\SVGRect();
        $svgRect->setValue('');
        $obj->writeNode($svgRect);
        $svgRect->setValue(null);
        $obj->writeNode($svgRect);
        $expect = $this->xmlDeclaration . '<rect /><rect />';
        $this->assertEquals($expect, $obj->getString());

        // should add zero as value
        $obj = new SVGWriter();
        $text = new \SVG\Nodes\Texts\SVGText('0');
        $obj->writeNode($text);
        $expect = $this->xmlDeclaration . '<text x="0" y="0">0</text>';
        $this->assertEquals($expect, $obj->getString());
    }
}
