<?php

require_once 'config.php';
require_once 'URLShortener.php';

// Attempt redirection if possible.
if ( isset( $_GET['r'] ) ) {
    $target = URLShortener::getTarget( $_GET['r'] );
    if ( !is_null( $target ) ) {
        header("Location: $target");
        die;
    }
}

// Create a new redirect if necessary.
$result = '';
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    if ( isset( $_POST['code'] ) && isset( $_POST['target'] ) ) {
        $code = URLShortener::createCode( $_POST['code'], $_POST['target'] );
        $t = htmlspecialchars( $_POST['target'] );
        if ( !is_null( $code ) ) {
            $r = $redirectURL . $code;
            $result = <<<HTML
            <p class="alert alert-success">
                <a class="alert-link" href="$t">$t</a> can be accessed at <a class="alert-link" href="$r">$r</a>.
            </p>
HTML;
        } else {
            // TODO: give a more descriptive reason
            $result = <<<HTML
            <p class="alert alert-danger">
                We failed to create your redirect for <em>$t</em>.
            </p>
HTML;
        }
    }
}

// Send output to the user.
echo <<<HTML
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>URL Shortener</title>

        <link href="assets/css/bootstrap.min.css" rel="stylesheet">
        <link href="assets/css/style.css" rel="stylesheet">

        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
            <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body>
        <header>
            <h1 class="page-header container">URL Shortener</h1>
        </header>

        <main class="container">
            $result
        
            <form method="post">
                <div class="form-group">
                    <label for="target">URL to shorten</label>
                    <input name="target" required="required" id="target" class="form-control" placeholder="http://example.com">
                </div>
                <div class="form-group">
                    <label>Custom URL code (optional)</label>
                    <div class="input-group">
                        <span id="code-label" class="input-group-addon">$redirectURL</span>
                        <input name="code" aria-describedby="code-label" class="form-control" placeholder="example">
                    </div>
                </div>
                <button class="btn btn-default btn-block">Shorten my URL!</button>
            </form>
        </main>

        <script src="assets/js/jquery-2.2.4.js"></script>
        <script src="assets/js/bootstrap.min.js"></script>
    </body>
</html>
HTML;
