{extends 'template.tpl'}
{block name="principal"}
    <div class="wrapper">
        {include file='partials/sidebar.tpl'}
        <div class="main-panel">
            <nav class="navbar navbar-default navbar-fixed">
                <div class="container-fluid">
                    <div class="navbar-header">
                        {include file='partials/toogle.tpl'}
                        <a class="navbar-brand" href="#">Assets Manager</a>

                    </div>
                    <div class="collapse navbar-collapse">
                        <ul class="nav navbar-nav navbar-left">
                        </ul>
                    </div>

                </div>
            </nav>
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">Options</h4>
                                    <p class="category">Click on one of the buttons to perform an action.</p>
                                </div>
                                <div class="content">
                                    <div class="row center-block text-center manager-options">
                                        <div class="col-md-4">
                                            <a class="btn-default btn publishallassets"  attr-href="{nocache}{route name='iumio_manager_assets_manager_publish' params=["appname" => "_all", "env" => "all"] } {/nocache}">Publish all</a>
                                        </div>
                                        <div class="col-md-4">
                                            <a class="btn-default btn publishalldevassets"  attr-href="{nocache}{route name='iumio_manager_assets_manager_publish' params=["appname" => "_all", "env" => "dev"] }{/nocache}">Publish dev</a>
                                        </div>
                                        <div class="col-md-4">
                                            <a class="btn-default btn publishallprodassets"  attr-href="{nocache}{route name='iumio_manager_assets_manager_publish' params=["appname" => "_all", "env" => "prod"] }{/nocache}">Publish prod</a>
                                        </div>
                                        <div class="col-md-4">
                                            <a class="btn-default btn clearallassets"  attr-href="{nocache}{route name='iumio_manager_assets_manager_clear' params=["appname" => "_all", "env" => "all"] }{/nocache}">Clear all</a>
                                        </div>
                                        <div class="col-md-4">
                                            <a class="btn-default btn clearalldevassets"  attr-href="{nocache}{route name='iumio_manager_assets_manager_clear' params=["appname" => "_all", "env" => "dev"] }{/nocache}">Clear for dev</a>
                                        </div>
                                        <div class="col-md-4">
                                            <a class="btn-default btn clearallprodassets"  attr-href="{nocache}{route name='iumio_manager_assets_manager_clear' params=["appname" => "_all", "env" => "prod"] }{/nocache}">Clear for prod</a>
                                        </div>
                                    </div>
                            </div>
                        </div>
                        </div>
                        <div class="col-md-12">
                            <div class="card">
                                <div class="header">
                                    <h4 class="title">List of your apps assets</h4>
                                    <p class="category">This is the list of your assets for each environment. You have the main information with the publication status on each environment. You can also perform actions on each compiled environment.</p>
                                </div>
                                <div class="content table-responsive table-full-width">
                                    <table class="table table-hover table-striped">
                                        <thead >
                                        <th>ID</th>
                                        <th>App name</th>
                                        <th>Assets directory</th>
                                        <th>Permissions dev</th>
                                        <th>Permissions prod</th>
                                        <th>Status dev</th>
                                        <th>Status prod</th>
                                        <th>Actions</th>
                                        </thead>
                                        <tbody class="getAllAssets" attr-href="{nocache}{route name='iumio_manager_assets_manager_get_all'}{/nocache}">

                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {include file='partials/footer.tpl'}

        </div>
    </div>
{/block}

