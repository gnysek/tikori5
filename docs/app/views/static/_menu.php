&bull; <?php echo HTML::link('Documentation main page', ''); ?>
<ol>
    <li><?php echo HTML::link('Instalation', 'static/installation'); ?></li>
    <li><?php echo HTML::link('Basic configuration', 'static/basic'); ?></li>
    <li><?php echo HTML::link('Request flow', 'static/request-flow'); ?></li>
    <li><?php echo HTML::link('Static apps', 'static/static'); ?></li>
    <li class="doc-empty"><?php echo HTML::link('Dynamic apps', 'static/dynamic'); ?>
        <ol>
            <li><?php echo HTML::link('Database connection', 'static/dynamic/database'); ?></li>
            <li><?php echo HTML::link('Predefined modules', 'static/dynamic/predefined'); ?>
                <ol>
                    <li><?php echo HTML::link('Content & Categories', 'static/dynamic/content'); ?>
                        <ul>
                            <li><?php echo HTML::link(
                                    'Types of content, Categories', 'static/dynamic/content/types'
                                ); ?></li>
                            <li><?php echo HTML::link('Front page', 'static/dynamic/content/frontpage'); ?></li>
                            <li><?php echo HTML::link('Comments', 'static/dynamic/content/comments'); ?></li>
                        </ul>
                    </li>
                    <li><a href="#">Downloads</a></li>
                    <li><a href="#">Users & Permissions</a>
                        <ul>
                            <li><a href="#">Profiles</a></li>
                            <li><a href="#">Permissions</a></li>
                            <li><a href="#">Groups</a></li>
                        </ul>
                    </li>
                    <li><a href="#">Forum</a>
                        <ul>
                            <li><a href="#">Categories</a></li>
                            <li><a href="#">Permissions</a></li>
                            <li><a href="#">Moderation</a></li>
                        </ul>
                    </li>
                    <li><a href="#">Shoutbox</a></li>
                    <li><a href="#">Tags</a></li>
                    <li><a href="#">RSS</a></li>
                </ol>
            </li>
        </ol>
    <li class="doc-empty"><a href="#">ACP (Admin Control Panel)</a></li>
    <li class="doc-empty"><a href="#">Multilanguages</a></li>
    <li class="doc-empty"><a href="#">Cache</a></li>
    <li><a href="#" class="doc-empty">Extending</a>
        <ol>
            <li><?php echo HTML::link('Controllers & Routes', 'static/extend/controllers-and-routes'); ?></li>
            <li class="doc-empty"><a href="#">Models</a></li>
            <li class="doc-empty"><a href="#">Custom SQL queries</a></li>
            <li class="doc-empty"><a href="#">Modules</a></li>
            <li class="doc-empty"><a href="#">Forms & Data Collection</a></li>
            <li class="doc-empty"><a href="#">RAD</a></li>
        </ol>
    </li>
</ol>
