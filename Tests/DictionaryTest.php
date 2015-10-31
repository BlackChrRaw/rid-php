<?php

namespace Cam5\RidPhp\Tests;

use Cam5\RidPhp\Service\Dictionary;

class DictionaryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @before
     */
    public function setupSomeFixtures()
    {
        $this->d = new Dictionary();
    }

    /**
     * @covers \Cam5\RidPhp\Service\Dictionary::initTemporaryValues
     * @covers \Cam5\RidPhp\Service\Dictionary::clearTemporaryValues
     */
    public function testClearTemporaryValues()
    {
        $this->d->initTemporaryValues();

        $this->assertInstanceOf('stdClass', $this->d->temporaryValues);

        $this->d->temporaryValues->foo = 'bar';
        $this->d->clearTemporaryValues();

        try {
          $this->d->temporaryValues->foo;
        } catch (\PHPUnit_Framework_Error_Notice $e) {
          return;
        }

        $this->fail('clearTemporaryValues did not clear the values it said it would.');
    }

    /**
     * @covers \Cam5\RidPhp\Service\Dictionary::getDefaultSource
     */
    public function testGetDefaultSource()
    {
        $dictionarySource = $this->d->getDefaultSource();
        $ridFile          = file_get_contents(dirname(__FILE__) . '/../Resource/RID.CAT');

        $this->assertEquals($ridFile, $dictionarySource);
    }

    /**
     * @covers \Cam5\RidPhp\Service\Dictionary::fixTabRead
     */
    public function testFixTabRead()
    {
        $this->assertEquals(
            'Primary',
            $this->d->fixTabRead('Primary', "\tCATEGORYNAME")
        );

        $this->assertEquals(
            'Secondary',
            $this->d->fixTabRead('Secondary', "\t\tCATEGORYNAME")
        );

        $this->assertEquals(
            'Tertiary',
            $this->d->fixTabRead('Tertiary', "\t\t\tCATEGORYNAME")
        );

        // The fix-case. Then it's a word, that was identified as a tertiary category.
        $this->assertEquals(
            'Word',
            $this->d->fixTabRead('Tertiary', "\t\tWORDNAME (1)")
        );

        // A lark; it's only looking to fix tertiary terms that might misidentify.
        $this->assertEquals(
            'Secondary',
            $this->d->fixTabRead('Secondary', "\t\tWORDNAME (1)")
        );
    }

    /**
     * @covers \Cam5\RidPhp\Service\Dictionary::getTargetCategory
     */
    public function testGetTargetCategory()
    {
        $this->assertEquals(
            'Primary',
            $this->d->getTargetCategory('Apple', 'Secondary', 'Secondary')
        );

        $this->assertEquals(
            'Secondary',
            $this->d->getTargetCategory('Banana', 'Tertiary', 'Tertiary')
        );

        $this->assertEquals(
            'Secondary',
            $this->d->getTargetCategory('Crabappble', 'Tertiary', 'Word')
        );

        $this->assertEquals(
            'None',
            $this->d->getTargetCategory('Dragonfruit', 'Primary', 'Primary')
        );
    }

    /**
     * @covers \Cam5\RidPhp\Service\Dictionary::initRecords
     */
    public function testInitRecords()
    {
        $this->d->initTemporaryValues();

        $this->assertInstanceOf('DomDocument', $this->d->records);

        return $this->d->records;
    }

    /**
     * @depends testInitRecords
     * @covers \Cam5\RidPhp\Service\Dictionary::handleCategoryNode
     */
    public function testHandleSubCategoryNode($records)
    {
        $this->d->initTemporaryValues();

        $nodeName   = 'GLORY';
        $node       = $records->createElement($nodeName);
        $parentNode = $this->d->temporaryValues->Primary = $records->createElement('PRIMARY');

        $result = $this->d->handleCategoryNode($node, 'Secondary');

        $this->assertInstanceOf('DomNode', $result);
        $this->assertEquals($nodeName, $result->nodeName);
        $this->assertEquals('Secondary', $result->getAttribute('level'));
    }

    /**
     * @depends testInitRecords
     * @covers \Cam5\RidPhp\Service\Dictionary::handleCategoryNode
     */
    public function testHandleParentCategoryNode($records)
    {
        $this->d->initTemporaryValues();

        $nodeName   = 'PRIMARY';
        $node       = $records->createElement($nodeName);

        $result = $this->d->handleCategoryNode($node, 'Primary');

        $this->assertInstanceOf('DomNode', $result);
        $this->assertEquals($nodeName, $result->nodeName);
        $this->assertEquals('#document', $result->parentNode->nodeName);
    }
}

