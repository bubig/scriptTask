<?php
$modFirst = 3;
$modSecond = 5;
for ($i = 1; $i <=100; $i++ ){
    if($i % $modSecond == 0  && $i % $modFirst == 0){
        // divided by 5 and 3 (no remainder)
        echo "foobar";
    }elseif( $i % $modSecond == 0 ){
        // divided by 5
        echo "bar";
    }else if ($i % $modFirst == 0 ){
        // divided by 3
        echo "foo";
    }else{
        echo $i;
    }
    if($i != 100) {
        echo ",";
    }
}
