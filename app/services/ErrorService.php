<?php
declare(strict_types=1);

use Phalcon\Di\DiInterface;

class ErrorService
{
    /**
     * @var DiInterface
     */
    protected $di;
    public function __construct(DiInterface $di)
    {
        $this->di = $di;
    }

    /**
     * public function error
     * Create error object (code, message, prior errors)
     * @param string $ERROR_CODE
     * @param string $ERROR_MESSAGE
     * @param array $ERROR_PRIOR
     * @return array
     */
    public function error($ERROR_CODE, $ERROR_MESSAGE = NULL, $ERROR_PRIOR = array())
    {
        //$ERROR_MESSAGE_DISPLAY is the user-friendly error message that will be displayed in various places
        $ERROR_MESSAGE_DISPLAY = "";
        //If there is prior error
        //If there is also 2nd-nth level prior error reorganize them
        if (!isset($ERROR_PRIOR['error_prior']) || !is_array($ERROR_PRIOR['error_prior'])) {
            $ERROR_PRIOR['error_prior'] = array();
        }
        if ($this->isError($ERROR_PRIOR)) {
            array_push($ERROR_PRIOR['error_prior'], array("error_code" => $ERROR_CODE, "error_message" => $ERROR_MESSAGE, "error_message_display" => $ERROR_MESSAGE_DISPLAY));
            $ERROR_MESSAGE = "{$ERROR_PRIOR['error_message']}|E:{$ERROR_CODE}";
            $ERROR_CODE = $ERROR_PRIOR['error_code'];
            $ERROR_MESSAGE_DISPLAY = $ERROR_PRIOR['error_message_display'];
        }
        $this->derror("error_code  => {$ERROR_CODE}, error_message => {$ERROR_MESSAGE}, error_message_display => {$ERROR_MESSAGE_DISPLAY}, error_prior => " . json_encode($ERROR_PRIOR));
        return array("error_code" => $ERROR_CODE, "error_message" => $ERROR_MESSAGE, "error_message_display" => $ERROR_MESSAGE_DISPLAY, "error_prior" => $ERROR_PRIOR['error_prior']);
    }

    /**
     * public function isError
     * Check if object is error array (has error_code and error_message)
     * @param mixed $RESULT_OBJECT
     * @return bool
     */
    public function isError($RESULT_OBJECT)
    {
        if (is_object($RESULT_OBJECT)) {
            return false;
        }
        $JSON_DECODED = $RESULT_OBJECT;
        if (isset($JSON_DECODED['error_code']) || isset($JSON_DECODED['error_message'])) {
            return true;
        } else {
            return false;
        }
    }

    private function getError($CODE)
    {
        if (!isset($this->_ERROR_DISPLAY[$CODE])) {
            return "";
        }
        return $this->_ERROR_DISPLAY[$CODE];
    }

    protected $_ERROR_DISPLAY = array(
        //Legend "Error Code" => "Error Message"
        "10" => "กรุณากรอก Email / เบอร์มือถือค่ะ",
        "11" => "กรุณากรอกรหัสผ่านเพื่อเข้าสู่ระบบค่ะ",
        "12" => "กรุณาตรวจสอบ Username หรือ รหัสผ่าน หากลืมรหัสผ่านกรุณารีเซ็ทรหัสผ่านคะ",
        "29" => "ข้อผิดพลาดในเรื่องข้อมูล กรุณาติดต่อเจ้าหน้าที่",
        "30" => "ข้อผิดพลาดในเรื่องข้อมูล กรุณาติดต่อเจ้าหน้าที่",
        "31" => "ไม่พบรายการ",
        "32" => "การชำระไม่สำเร็จ กรุณาลองใหม่อีกครั้ง หรือ ติดต่อเจ้าหน้าที่",
        "33" => "เกิดข้อผิดพลาดในระบบ กรุณาติดต่อเจ้าหน้าที่",
        "34" => "ไม่พบรายการ",
        "35" => "เกิดข้อผิดพลาดในระบบ กรุณาติดต่อเจ้าหน้าที่",
        "36" => "เกิดข้อผิดพลาดในระบบ กรุณาติดต่อเจ้าหน้าที่",
        "37" => "เกิดข้อผิดพลาดในระบบ กรุณาติดต่อเจ้าหน้าที่",
        "38" => "ไม่พบรายการ",
        "39" => "ไม่พบรายการคอรส์เรียนในระบบ",
        //Sign in (Web)
        "47" => "กรุณากรอก อีเมล / เบอร์มือถือ แล้วลองใหม่อีกครั้งคะ",
        "48" => "กรุณากรอก 'รหัสผ่าน' แล้วลองใหม่อีกครั้งคะ",
        "49" => "กรุณาตรวจสอบ อีเมล หรือ รหัสผ่านแล้วลองใหม่อีกครั้งคะ หากไม่ได้กรุณาลอง 'รีเซ็ทรหัสผ่าน' โดยการกดที่ 'ลืมรหัสผ่าน'",
        "2071" => "ไม่สามารถถอนเงินได้ คุณอาจะยังไม่ได้แจ้งบัญชีถอนเงิน",
        "2075" => "กรุณาเติมเงินเนื่องจากคุณมียอดเงินไม่พอ",
        "2084" => "กรุณากรอกเบอร์โทรศัพท์เพื่อบันทึกข้อมูล",
        "2087" => "ไม่สามารถถอนเงินได้เนื่องจากคุณยังไม่ได้กำหนดช่องทางการรับเงิน",
        //Sign in
        "3000" => "รูปแบบอีเมลไม่ถูกต้องคะ",
        "3001" => "อีเมลนี้ได้ถูกใช้งานแล้วคะ",
        "3002" => "กรุณาระบุชื่อจริงและนามสกุลให้ครบถ้วนคะ",
        "3004" => "อีเมลนี้ได้ถูกใช้งานแล้วคะ",
        "3008" => "รหัสผ่านที่กำหนด และ รหัสผ่านที่คุณยืนยันไม่ตรงกันคะ",
        //Sign UP
        "4000" => "กรุณาตรวจสอบอีเมล์ให้ถูกต้องและลองใหม่อีกครั้งคะ",
        "4078" => "ไฟล์รูปไม่อยู่ในชนิด ('gif', 'jpg', 'jpeg', 'png') หรือ ไฟล์รูปอยู่ในรูปแบบไม่สมบูรณ์",
        //Master Admin
        "5086" => "กรุณาแนบรูปหน้าสมุดเงินฝาก เพื่อประกอบการลงทะเบียนบัญชีคะ",
        //Finance
        "5210" => "มีความผิดพลาดในการถอนบางรายการ กรุณาติดต่อเจ้าหน้าที่เพื่อทำการแก้ไขค่ะ",
        "5214" => "คุณยังไม่ได้ระบุช่องทางการรับเงินในระบบ",
        //User id
        "6203" => "User ID มีความยาวเกินที่กำหนด",
        //Owner Document
        "6349" => "ไม่สามารถ Upload File ได้เนื่องจากไม่ใช่รูปชนิด (.jpg, .gif, .png)",
        //Payment Invoice
        "7005" => "ตั๋วนี้มีสถานะใช้งานแล้ว",
        //Review
        "7517" => "ไม่สามารถรีวิวซ้ำได้",
        //Event
        "17000" => "ไม่พบรายชื่อกิจกรรม",
        "17001" => "ไม่พบรายชื่อกิจกรรม",
        "17002" => "ผู้ใช้งานผิดประเภท",
        "17003" => "รหัสผู้ใช้งานผิดพลาด",
        "17004" => "รหัสกิจกรรมผิดพลาด",
        "17005" => "ไม่พบผู้ใช้งานในรายชื่อ Guest กิจกรรม",
        "17006" => "ไม่พบผู้ใช้งานในรายชื่อ Guest กิจกรรม",
        "17007" => "ไม่พบผู้ใช้งานในรายชื่อ Guest กิจกรรม",
        "17008" => "ไม่พบผู้ใช้งานในรายชื่อ Guest กิจกรรม",
        "17009" => "คุณได้ทำการ Check-in กิจกรรมแล้ว",
    );

    private function dm($MSG)
    {
        $IS_CLI = (PHP_SAPI == 'cli' ? TRUE : FALSE);
        $LINE_FEED = ($IS_CLI == TRUE ? "\r\n" : "<br/>");
        $TRACE = debug_backtrace();
        if (isset($TRACE[1])) {
            $CALLER = $TRACE[1];
        } else {
            $CALLER = NULL;
        }
        if (!$IS_CLI) {
            $SPANSTART = "<strong style='color:black'>";
            $SPANEND = "</strong>";
            if (isset($CALLER['file']) && isset($CALLER['line'])) {
                $CALLING_FILE = (isset($CALLER['file']) ? $CALLER['file'] : "");
                $CALLING_LINE = (isset($CALLER['line']) ? $CALLER['line'] : "");
            } else {
                $CALLING_FILE = $CALLER["function"];
                $CALLING_LINE = $CALLER["args"][0];
            }
            $MSG = "{$SPANSTART}{$CALLING_FILE} - [Line:{$CALLING_LINE}]{$SPANEND} {$MSG}";
        } else {
            if (isset($CALLER['file']) && isset($CALLER['line'])) {
                $CALLING_FILE = (isset($CALLER['file']) ? $CALLER['file'] : "");
                $CALLING_LINE = (isset($CALLER['line']) ? $CALLER['line'] : "");
            } else {
                $CALLING_FILE = $CALLER["function"];
                $CALLING_LINE = $CALLER["args"][0];
            }
            $MSG = "{$CALLING_FILE} - [Line:{$CALLING_LINE}] {$MSG}";
        }
        echo "{$MSG}{$LINE_FEED}";
    }

    private function dinfo($MSG)
    {
        $this->dmc($MSG, "black", "#BDEDFF");
    }

    private function dwarn($MSG)
    {
        $this->dmc($MSG, "black", "orange");
    }

    private function derror($MSG)
    {
        $this->dmc($MSG, "white", "red");
    }

    private function dsuccess($MSG)
    {
        $this->dmc($MSG, "white", "green");
    }

    private function dmc($MSG, $COLOR, $BGCOLOR = "white")
    {
        $this->dm("<div style='color:{$COLOR};background-color:{$BGCOLOR}'>{$MSG}</div>");
    }
}