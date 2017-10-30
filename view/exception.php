<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Unhandled Exception</title>

    <style>
        .crl-exception-header {
            background: lightgrey;
            color: #333;
            padding: 2rem 3rem;
            font-family: inherit;
            font-weight: 500;
            line-height: 1.1;
            width: 100%;
            font-size: 2rem;
        }

        body {
            margin: 0;
            overflow-x: hidden;
            background: whitesmoke;
        }

        .crl-red-bold {
            color: darkred;
            font-weight: bold;
        }

        .crl-in-file {
            color: grey;
            font-size: 1.5rem;
        }

        .crl-exception-frame {
            width: 100%;
            padding-top: 2rem;
            line-height: 2rem;
            font-size: 1rem;
            border: none;
        }

        .crl-exception-line {
            background: #e5e5e5;
            padding: .5rem 2rem;
        }

        .crl-exception-line div {
            display: inline-block;
        }
        .crl-exception-file {
            color: grey;
            padding-left: 10px;
            padding-right: 15px;
        }
        .crl-exception-row {
            float: right;
            text-align: right;
        }
        pre {
            line-height: 0;
            padding: 5px 2rem;
            width: 100%;
            margin: 0;
            text-align: left;
            white-space: pre-line;
        }
        pre p {
            -webkit-margin-before: 0;
            -webkit-margin-after: 0;
            -webkit-margin-start: 0;
            -webkit-margin-end: 0;
            padding: 5px 10px;
            line-height: .6rem;
        }
        .crl-exception-row-line {
            width: 100%;
        }
        .crl-exception-row-line span:first-child {
            padding-right: 10px;
            border-right: 1px solid grey;
        }

        .crl-exception-row-line-red {
            background: rgba(255, 0, 0, 0.20);
        }


    </style>
</head>
<body>

<div class="crl-exception-header">
    <span class="crl-red-bold">Exception:</span> <?= $exception->getMessage(); ?>
    <span class="crl-in-file">in <?= basename($exception->getFile()) ?></span>
</div>

<div class="crl-exception-frame">

    <div class="crl-exception-line"></div>
    <div class="crl-exception-line">
        <div>#1</div>
        <div class="crl-exception-file">in <?= $exception->getFile() ?></div>
        <div class="crl-exception-row">at line <?= $exception->getLine() ?></div>
    </div>
    <?php if (is_array($lines) && !empty($lines)) { ?>
        <pre>
                <?php foreach ($lines as $rownum => $line) { ?>
                    <p class="crl-exception-row-line <?= ($rownum == $exception->getLine() ? 'crl-exception-row-line-red' : '') ?>"
                    ><span><?=$rownum ?></span><span><?=$line ?></span>
                    </p>
                <?php } ?>
            </pre>
    <?php } ?>

    <?php foreach ($exception->getTrace() as $counter => $trace) { ?>
        <?php if (isset($trace['file']) && isset($trace['type']) && isset($trace['function']) && isset($trace['line'])) { ?>
        <div class="crl-exception-line">
            <div><?= '#' . ($counter + 2) ?></div>
            <div class="crl-exception-file">in <?= $trace['file'] ?></div>
            <div class="crl-exception-func"> <?= $trace['type'].$trace['function'].'()' ?></div>
            <div class="crl-exception-row">at line <?= $trace['line'] ?></div>
        </div>

    <?php }} ?>
</div>


</body>
</html>






