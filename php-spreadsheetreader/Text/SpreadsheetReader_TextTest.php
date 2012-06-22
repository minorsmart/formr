<?php
// Call SpreadsheetReader_TextTest::main() if this source file is executed directly.
if (!defined("PHPUnit_MAIN_METHOD")) {
    define("PHPUnit_MAIN_METHOD", "SpreadsheetReader_TextTest::main");
}

require_once "PHPUnit/Framework/TestCase.php";
require_once "PHPUnit/Framework/TestSuite.php";

require_once 'SpreadsheetReader_Text.php';

/**
 * Test class for SpreadsheetReader_Text.
 * Generated by PHPUnit_Util_Skeleton on 2007-03-17 at 08:44:27.
 */
class SpreadsheetReader_TextTest extends PHPUnit_Framework_TestCase {
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main() {
        require_once "PHPUnit/TextUI/TestRunner.php";

        $suite  = new PHPUnit_Framework_TestSuite("SpreadsheetReader_TextTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    public $reader;
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp() {
        $this->reader = new SpreadsheetReader_Text;
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown() {
    }

    /**
     * case: pattern set and get.
     * 
     */
    public function testPattern() {
        // Remove the following line when you implement this test.
        $p = '/(\d+)\s+(\w+)/';
        $result = $this->reader->pattern($p);
        $this->assertEquals($result, $p);
        $p = $this->reader->pattern();
        $this->assertEquals($p, $result);
        
        $result = $this->reader->pattern(false); //disable pattern
        $this->assertFalse($result);
    }

    /**
     * case: �ɮפ��s�b�C
     * result: FALSE
     *
     * @test
     */
    public function SpreadsheetFileIsNotExisted() {
        $sheetFilePath = '';
        $sheets = $this->reader->read($sheetFilePath);
        $this->assertFalse($sheets);
    }

    /**
     * case: Ū�� test.txt
     * result:
     *  1 sheets.
     *  44 rows of first sheet.
     *  11 columns of row 10 of first sheet.
     *
     * @test
     */
    public function ReadFromTextFile() {
        $sheetFilePath = 'test.txt';
        $sheets = $this->reader->read($sheetFilePath);
        $this->assertEquals(1, count($sheets));
        $this->assertEquals(44, count($sheets[0]));
        $this->assertEquals(11, count($sheets[0][10]));
        $this->assertEquals(510, $sheets[0][10][5]);
    }

    /**
     * case: Ū�� test2.txt
     * result:
     *  1 sheets.
     *  103 rows of first sheet.
     *
     * @test
     */
    public function ReadFromTextFileWithPattern() {
        $sheetFilePath = 'test2.txt';

        $pattern = '/(?P<ean>\d{1,13})\s*(\d+)?/';
        $this->reader->pattern($pattern);

        $sheets = $this->reader->read($sheetFilePath);
        $this->assertEquals(1, count($sheets));
        $this->assertEquals(103, count($sheets[0]));

        $this->assertEquals(101090035, $sheets[0][0][0]);
        $this->assertEquals(1800, $sheets[0][1][1]);
        
        $this->assertEquals(1040100762215, $sheets[0][2][0]);
        $this->assertEquals(100100, $sheets[0][2][1]);

        $this->assertFalse(isset($sheets[0][7][1]));
    }

}

// Call SpreadsheetReader_TextTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "SpreadsheetReader_TextTest::main") {
    SpreadsheetReader_TextTest::main();
}
?>