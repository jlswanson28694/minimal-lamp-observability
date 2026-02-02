<?php
    if(rand(0,10) <= 1) {
        throw new \Exception();
    }

    // echo phpinfo();
    usleep(rand(2_000, 400_000));
    // sleep(2);
?>

Hello World