<style>
    .dbg-close {
        float: right;
        margin-left: 2px;
    }

    .dbg-close div {
        cursor: pointer;
        padding: 3px 10px;
        background: #333;
        border-radius: 3px;
    }

    .dbg-close:hover div {
        background: #666;
    }

    .dbg-tab-link {
        padding: 5px 28px 5px 28px;
        display: inline-block;
        background: darkcyan;
        color: white;
        cursor: pointer;
        border-radius: 4px;
    }

    .dbg-tab-link.active {
        padding: 5px 15px;
    }

    .dbg-tab-link:hover {
        color: black;
    }

    .dbg-tab-link .dbg-tab-close {
        display: none;
        padding: 0px 7px;
        border-radius: 4px;
        margin-left: 4px;
    }

    .dbg-tab-link.active .dbg-tab-close {
        display: inline-block;
        visibility: visible;
        background: tomato;
        color: white;
    }

    #tab-SQL code {
        display: block;
        margin-top: 5px;
    }

    .dbg-toolbar {
        position: fixed;
        top: 0;
        left: 0px;
        bottom: 38px;
        background: darkgrey;
        width: 100%;
        z-index: 2000;
        display: none;
    }

    .dbg-toolbar > div {
        overflow-y: scroll;
        height: 100%;
        padding: 10px 5px;
    }

    #tikori-dbg-toolbar {
        box-sizing: border-box;
        position: fixed;
        width: 100%;
        height: 38px;
        right: 0px;
        bottom: 0px;
        background: black;
        color: white;
        padding: 5px 10px;
        z-index: 1000;
        font-size: 12px;
    }

    #tikori-dbg-inner {
        float: left;
    }

    .dbg-notfications-count {
        background: orange;
        border-radius: 4px;
        padding: 0 4px;
    }
</style>

<?php $tabs[] = 'unusedCss';
$nf['unusedCss'] = 0;
$values['unusedCss'] = array('<div id="unusedCssDbgTab">getting css...</div>'); ?>

<div id="tikori-dbg-toolbar">

    <div id="tikori-dbg-inner">
        TOOLBAR

        <?php foreach ($tabs as $tab): ?>
            <a onclick="activateTab('<?php echo $tab; ?>', this);" class="dbg-tab-link" id="tab-btn-<?php echo $tab; ?>">
                <?php echo ucfirst($tab); ?>
                <?php if (array_key_exists($tab, $nf)): ?>
                    <span class="dbg-notfications-count"><?php echo $nf[$tab]; ?></span>
                <?php endif; ?>
                <span class="dbg-tab-close">&times;</span>
            </a>
        <?php endforeach; ?>
    </div>

    <script>
        function activateTab(tab, self) {
            //console.log($(self));
            $('[data-dbg-tab]:not(#tab-' + tab + ')').hide();
            $('#tab-' + tab + '').toggle();
            $('#tikori-dbg-inner > a').removeClass('active');
            $(self).toggleClass('active', $('#tab-' + tab + '').is(':visible') ? true : false);
        }

        function showOrHide() {
            var show = !$('#tikori-dbg-inner').is(':visible');

            if (!show) {
                $('[data-dbg-tab]').hide();
                $('#tikori-dbg-inner > a').removeClass('active');
            }

            $('#tikori-dbg-toolbar').css('width', show ? '100%' : '80px');
            $('#tikori-dbg-toolbar').css('width', show ? '100%' : '80px');
            $('#tikori-dbg-inner').toggle(show);
            $('[data-dbg-toggle] div').html(show ? '&raquo;' : '&laquo;');

            $(this).blur();
        }
    </script>

    <div class="dbg-close">
        <div onclick="$('#tikori-dbg-toolbar , [data-dbg-tab]').remove();">&times;</div>
    </div>

    <div class="dbg-close" data-dbg-toggle>
        <div onclick="showOrHide();">&raquo;</div>
    </div>

    <div style="float: right; line-height: 27px;">
        <?php echo $status; ?>
    </div>

</div>

<?php foreach ($tabs as $tab): ?>
    <div class="dbg-toolbar" id="tab-<?php echo $tab; ?>" data-dbg-tab>
        <div>
            <?php foreach ($values[$tab] as $content): ?>
                <?php echo $content; ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>

<script type="application/javascript">
    /*
    https://gist.github.com/kdzwinel/426a0f76f113643fa285
    This script attempts to identify all CSS classes mentioned in HTML but not defined in the stylesheets.
    In order to use it, just run it in the DevTools console (or add it to DevTools Snippets and run it from there).
    Note that this script requires browser to support `fetch` and some ES6 features (fat arrow, Promises, Array.from, Set). You can transpile it to ES5 here: https://babeljs.io/repl/ .
    Known limitations:
    - it won't be able to take into account some external stylesheets (if CORS isn't set up)
    - it will produce false negatives for classes that are mentioned in the comments.
    */

    (function () {
        "use strict";

        //get all unique CSS classes defined in the main document
        let allClasses = Array.from(document.querySelectorAll('*'))
            .map(n => Array.from(n.classList))
            .reduce((all, a) => all ? all.concat(a) : a)
            .reduce((all, i) => all.add(i), new Set());

        //load contents of all CSS stylesheets applied to the document
        let loadStyleSheets = Array.from(document.styleSheets)
            .map(s => {
                if (s.href) {
                    return fetch(s.href)
                        .then(r => r.text())
                        .catch(e => {
                            console.warn('Coudn\'t load ' + s.href + ' - skipping');
                            return "";
                        });
                }

                return s.ownerNode.innerText
            });

        Promise.all(loadStyleSheets).then(s => {
            let text = s.reduce((all, s) => all + s);

            //get a list of all CSS classes that are not mentioned in the stylesheets
            let undefinedClasses = Array.from(allClasses)
                .filter(c => {
                    var rgx = new RegExp(escapeRegExp('.' + c) + '[^_a-zA-Z0-9-]');

                    return !rgx.test(text);
                });

            if (undefinedClasses.length) {
                var onc = ' onclick="markCss(this);"'
                $('#unusedCssDbgTab').html('List of ' + undefinedClasses.length + ' undefined CSS classes:<br><code' + onc + '>' + undefinedClasses.join('</code><br><code' + onc + '>') + '</code>');
                $('#tab-btn-unusedCss .dbg-notfications-count').text(undefinedClasses.length);
            } else {
                $('#unusedCssDbgTab').text('All CSS classes are defined!');
                $('#tab-btn-unusedCss .dbg-notfications-count').remove();
            }
        });

        function escapeRegExp(str) {
            return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
        }

    })();

    function markCss(o) {
        var self = $(o);
        var selector = '.' + self.text();

        $('.debug-mark').removeClass('debug-mark');
        $(selector).addClass('debug-mark');

        activateTab('-', '-');

        var _top = Math.max(50, $(selector).first().offset().top - 20);

        $('html, body').animate({
            scrollTop: _top
        }, 100);
    }
</script>

<style>
    #unusedCssDbgTab code {
        cursor: pointer;
    }

    #unusedCssDbgTab code:hover {
        background: silver;
    }

    .debug-mark {
        position: relative;
    }

    .debug-mark::after {
        content: " ";
        position: absolute;
        display: block;
        visibility: visible;
        border: 2px dashed red;
        border-radius: 0px;
        z-index: 1000;
        width: 100%;
        height: 100%;

        left: 0;
        top: 0;

        background-color: rgba(255, 0, 0, 0.5);
    }
</style>