<?php
$root = $this->GetRootActivity();
$Coordinators = $root->GetVariable("Coordinators");
$count = count($Coordinators->GetValue());
$root->SetVariable('count', $count);