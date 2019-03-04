<style>
    .dbg-close {
        position: absolute;
        right: 10px;
        top: 8px;
    }

    .dbg-close div {
        cursor: pointer;
        padding: 3px 10px;
        background: #333;
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
        margin-right: 65px;
    }

    .dbg-notfications-count {
        background: orange;
        padding: 0 4px;
        color: black;
    }

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

    .tikori-dbg-counters {
        display: inline-block;
        padding: 5px 10px;
        background: #444;
        position: relative;
    }

    .tikori-dbg-counters .tikori-dbg-counters-container {
        display: none;
    }

    .tikori-dbg-counters:hover .tikori-dbg-counters-container {
        display: block;
        position: absolute;
        bottom: 30px;
        left: 0;
        background: black;
        min-width: 150px;
        padding: 5px;
    }

    .tikori-dbg-counters-container span {
        background: orange;
        padding: 0 10px;
        margin-left: 10px;
        color: black;
        white-space: nowrap;
    }

    .tikori-dbg-counters-container tr td:first-child {
        white-space: pre;
    }
</style>

<?php
$tabs[] = 'unusedCss';
$nf['unusedCss'] = 0;
$values['unusedCss'] = array('<div id="unusedCssDbgTab">getting css...</div>');
?>

<div id="tikori-dbg-toolbar">

    <div id="tikori-dbg-inner">
        <?php if (count($counters)): ?>
            <div class="tikori-dbg-counters">
                &times;<?php echo count($counters); ?>
                <div class="tikori-dbg-counters-container">
                    <table>
                        <?php foreach ($counters as $name => $cnt): ?>
                            <tr>
                                <td><?= $name ?></td>
                                <td><span><?= $cnt ?>&times;</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if (count($timers)): ?>
            <div class="tikori-dbg-counters">
                <?php echo round(array_sum($timers), 2); ?>s
                <div class="tikori-dbg-counters-container">
                    <table>
                        <?php foreach ($timers as $name => $cnt): ?>
                            <?php
                            $roundedTime = round($cnt, 3);
                            ?>
                            <tr>
                                <td><?= $name ?></td>
                                <td><span><?= ($roundedTime == 0 ? '&lt;' : '') . round($cnt, 3) . 's' ?></span></td>
                                <td><span><?php if ($cnt < 1): ?>
                                            <?php echo $cnt * 1000;
                                            echo ' ms'; ?>
                                        <?php else: ?>
                                            <?= '&ndash;'; ?>
                                        <?php endif; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php foreach ($tabs as $tab): ?>
            <a onclick="activateTab('<?php echo $tab; ?>', this);" class="dbg-tab-link" id="tab-btn-<?php echo $tab; ?>">
                <?php echo ucfirst($tab); ?>
                <?php if (array_key_exists($tab, $nf)): ?>
                    <span class="dbg-notfications-count"><?php echo $nf[$tab]; ?></span>
                <?php endif; ?>
                <span class="dbg-tab-close">&times;</span>
            </a>
        <?php endforeach; ?>

        <div style="display: inline-block;">
            &nbsp;<a class="dbg-tab-link" onclick="highlightTemplates();" style="padding: 5px 10px;" title="Template Hints">T</a>
        </div>

        <div style="float: right; line-height: 27px;">
            <?php echo $status; ?>
        </div>
    </div>
    <script type="text/javascript">

        var toggleToolbarCookie = function () {
        };

        if (Cookies) {
            toggleToolbarCookie = function (hidden) {
                Cookies.set('___debug_toolbar_mode', hidden ? '1' : '0', 365);
            }
        }

        function activateTab(tab, self) {
            //console.log($(self));
            $('[data-dbg-tab]:not(#tab-' + tab + ')').hide();
            $('#tab-' + tab + '').toggle();
            $('#tikori-dbg-inner > a').removeClass('active');
            $(self).toggleClass('active', $('#tab-' + tab + '').is(':visible') ? true : false);
        }

        function showOrHide() {
            var show = !$('#tikori-dbg-inner').is(':visible');
            toggleToolbarCookie(!show);

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

        <?php if (Core::app()->cookie->getClean('___debug_toolbar_mode') == '1'): ?>
        $().ready(function () {
            showOrHide();
        });
        <?php endif; ?>

        function highlightTemplates() {
            if ($('.xxx-debug').length == 0) {
                $("*").contents().filter(function () {
                    return this.nodeType == 8;
                }).each(function (i, e) {
                    if (e.data.match(/START/g)) {
                        $(e).replaceWith('<div class="xxx-comment-start xxx-debug"></div><div class="xxx-debug" style="background:red; padding: 4px; border: 1px solid purple; border-width: 1px 1px 0 1px;">' + e.data + '</div>');
                    }
                    if (e.data.match(/END/g)) {
                        $(e).replaceWith('<div class="xxx-comment-end xxx-debug"></div>');
                    }
                });
                $.each($('.xxx-comment-start'), function () {
                    $(this).nextUntil('.xxx-comment-end').wrapAll('<div style="border: 1px solid purple; border-width: 0 1px 1px 1px; margin: 2px;"></div>');
                });
            }
        }
    </script>

    <div class="dbg-close" data-dbg-toggle style="right: 43px;">
        <div onclick="showOrHide();">&raquo;</div>
    </div>

    <div class="dbg-close">
        <div onclick="$('#tikori-dbg-toolbar , [data-dbg-tab]').remove();">&times;</div>
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

<?php /* unused css */ ?>
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