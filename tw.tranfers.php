<?php

class WalletTranfers{
    public $Pin = null;

    public function __construct($Pin = null){
        if(is_null($Pin)) return false;
        
        $this->Pin = $Pin;
    }

    public function Transfers(){
        $this->Query("input tap 417 1497");
        $this->Query("input tap 417 1497");
        if($this->IsLogin()){
            return $this->SaveTransfers();
        }

        return false;
    }

    public function SaveTransfers(){
        $Array = [];
        print_r(["start" => date("d/m/Y H:i:s")]);
        for($i = 0; $i < 3; $i++){
            $this->Query("uiautomator dump /storage/emulated/0/Pictures/get_tranfers.xml");
            $List = $this->XmlToArray($this->Query("cat /storage/emulated/0/Pictures/get_tranfers.xml"));
            $List = $List["node"]["node"][0]["node"]["node"]["node"]["node"]["node"][1]["node"]["node"]["node"]["node"]["node"] ?? null;
            if(isset($List)){
                foreach($List as $Num => $Data){
                    $Pos = $Data["node"][1]["node"][0]["@attributes"] ?? null;
                    if(isset($Pos["bounds"]) && (strpos($Pos["text"], "รับเงินจาก") > -1 || strpos($Pos["text"], "โอนเงินให้") > -1)){
                        if($Num != 8){
                            $Pos["bounds"] = str_replace(",", " ", $this->GetStringBetween($Pos["bounds"], "[", "]["));
                            $this->Query("input tap ". $Pos["bounds"]);
                            $this->Query("uiautomator dump /storage/emulated/0/Pictures/get_treanfers_result.xml");
                            $Result = $this->XmlToArray($this->Query("cat /storage/emulated/0/Pictures/get_treanfers_result.xml"));
                            foreach($Result as $Data){
                                $Row = $Data["node"][0]["node"]["node"]["node"]["node"]["node"][1]["node"]["node"][0]["node"]["node"]["node"]["node"] ?? null;
                                if(isset($Row)){
                                    $Phone = $Row[2]["node"][0]["node"]["node"][0]["node"]["node"][1]["@attributes"]["text"];
                                    $FullName = $Row[2]["node"][1]["node"]["node"]["node"]["node"][1]["@attributes"]["text"];
                                    $Amount = floatval($Row[4]["node"][0]["node"]["node"]["node"][0]["node"][1]["@attributes"]["text"]);
                                    $DateTime = $Row[6]["node"]["node"]["node"]["node"][0]["node"][1]["@attributes"]["text"];
                                    $TranferID = $Row[6]["node"]["node"]["node"]["node"][1]["node"][1]["@attributes"]["text"];
                                    array_push($Array, [
                                        "phone" => $Phone,
                                        "fullname" => $FullName,
                                        "type" => strpos($Pos["text"], "รับเงินจาก") > -1 ? "creditor" : "debtor",
                                        "amount" => $Amount,
                                        "datetime" => $DateTime,
                                        "tranfer_id" => $TranferID,
                                    ]);
                                }
                            }
                            $this->Home();
                        }
                    }
                }
            }
            $this->Query("input swipe 100 500 100 -150");
        }
        $this->Home(); sleep(1); $this->Home();
        print_r(["end" => date("d/m/Y H:i:s")]);
        return $Array;
    }

    public function IsLogin(){
        $this->Query("uiautomator dump /storage/emulated/0/Pictures/check_login.xml");
        $Checking = $this->Query("cat /storage/emulated/0/Pictures/check_login.xml");
        if(strpos($Checking, "เข้าสู่ระบบ") > -1){
            $this->Query("input tap 466 1342");
            $this->SetPin();
        }

        return true;
    }

    public function SetPin(){
        $this->Query("input text ". $this->Pin);
    }

    public function Home(){
        $this->Query("input tap 53 1497");
    }

    public function Query($Command = null){
        if(is_null($Command)) return false;

        return shell_exec("adb -s 127.0.0.1:62001 shell " . $Command);
    }

    function XmlToArray($Xml){
        $XML = simplexml_load_string($Xml, "SimpleXMLElement", LIBXML_NOCDATA);
        $Json = json_encode($XML);
        $Array = json_decode($Json,TRUE);
        return $Array;
    }

    function GetStringBetween($string, $start, $end){
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }
}