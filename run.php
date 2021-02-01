<?php

require_once "tw.tranfers.php";

$tw = new WalletTranfers('PIN');
print_r($tw->Transfers());