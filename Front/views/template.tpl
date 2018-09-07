<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <link rel="icon" type="image/png" href="{img_manager name='favicon/favicon.ico'}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

    <title>Framework Graphic Manager</title>

    <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport' />
    <meta name="viewport" content="width=device-width" />

    {bootstrap_css min='yes'}
    {animate_css min='yes'}
    {css_manager name='light-bootstrap-dashboard'}
    {css_manager name='demo'}
    {css_manager name='index'}

    <!--     Fonts and icons     -->
    {fawesome_css min='yes'}
    {css_manager name='light-bootstrap-dashboard'}
    {css_manager name='pe-icon-7-stroke'}

</head>

<body>
<div class="iumio-loader-gen"> <h3>{$loader_msg}</h3> </div>

{block name="principal"}
{/block}

{include file='partials/modal.tpl'}
</div>
</div>

<!--   Core JS Files   -->
{jquery}
{bootstrap_js min='yes'}
<!--  Checkbox, Radio & Switch Plugins -->
{js_manager name='bootstrap-checkbox-radio-switch'}
<!--  Notifications Plugin    -->
{js_manager name='bootstrap-notify'}
<!-- Light Bootstrap Table Core javascript and methods for Demo purpose -->
{js_manager name='light-bootstrap-dashboard'}

{js_manager name='demo'}

{js_manager name='main'}

</body>
</html>