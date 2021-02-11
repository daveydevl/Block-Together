<?php

interface Methods {
    function _init();
    
    function beginStage($stage);
    function finishStage($data);
    
    function unlockChara($chara);
    function unlockRankAttack();
    function unlockRankHealth();
    function unlockRankFlame();
    
    function changeChara($chara);
    function changeName($name);
}
