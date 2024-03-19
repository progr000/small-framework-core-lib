<?php
/** @var array $__sql */
/** @var array $__session */
/** @var array $__route */
/** @var array $__view */
/** @var array $__timeline */
/** @var string $__memory */
/** @var string $__DEBUG_DATA */

/* timing */
$total_t = round(($__timeline['AppFinish'] - $__timeline['BootStart']) * 1000, 2);
$boot_t = round(($__timeline['BootFinish'] - $__timeline['BootStart']) * 1000, 2);
$app_t = round(($__timeline['AppFinish'] - $__timeline['AppStart']) * 1000, 2);
$total_p = 100;
$boot_p = round($boot_t * $total_p / $total_t, 2);
$app_p = $total_p - $boot_p;
$sql_total_t = 0;
foreach ($__sql as $item) {
    $sql_total_t += round(($item['sqlTimeFinish'] - $item['sqlTimeStart']) * 1000, 2);
}
?>
<div class="phpdebugbar">
    <div class="phpdebugbar-drag-capture"></div>
    <div class="phpdebugbar-resize-handle"></div>

    <div class="phpdebugbar-header">
        <div class="phpdebugbar-header-left">
            <a class="phpdebugbar-tab js-timeline-data" data-tab="timeline-data">
                <i class="phpdebugbar-fa phpdebugbar-fa-tasks"></i>
                <span class="phpdebugbar-text">Timeline</span>
                <span class="phpdebugbar-badge"></span>
            </a>
            <a class="phpdebugbar-tab js-view-data" data-tab="view-data">
                <i class="phpdebugbar-fa phpdebugbar-fa-leaf"></i>
                <span class="phpdebugbar-text">Views</span>
                <span class="phpdebugbar-badge phpdebugbar-visible"><?= count($__view) ?></span>
            </a>
            <a class="phpdebugbar-tab js-sql-data" data-tab="sql-data">
                <i class="phpdebugbar-fa phpdebugbar-fa-database"></i>
                <span class="phpdebugbar-text">Queries</span>
                <span class="phpdebugbar-badge phpdebugbar-visible"><?= count($__sql) ?></span>
            </a>
            <a class="phpdebugbar-tab js-session-data" data-tab="session-data">
                <i class="phpdebugbar-fa phpdebugbar-fa-archive"></i>
                <span class="phpdebugbar-text">Session</span>
                <span class="phpdebugbar-badge"></span>
            </a>
            <a class="phpdebugbar-tab js-route-data" data-tab="route-data">
                <i class="phpdebugbar-fa phpdebugbar-fa-share"></i>
                <span class="phpdebugbar-text">Route</span>
                <span class="phpdebugbar-badge"></span>
            </a>
            <a class="phpdebugbar-tab js-request-data" data-tab="request-data">
                <i class="phpdebugbar-fa phpdebugbar-fa-tags"></i>
                <span class="phpdebugbar-text">Request</span>
                <span class="phpdebugbar-badge"></span>
            </a>
            <a class="phpdebugbar-tab js-response-data" data-tab="response-data">
                <i class="phpdebugbar-fa phpdebugbar-fa-tags"></i>
                <span class="phpdebugbar-text">Response</span>
                <span class="phpdebugbar-badge"></span>
            </a>
            <a class="phpdebugbar-tab js-dump-data phpdebugbar-active" data-tab="dump-data">
                <i class="phpdebugbar-fa phpdebugbar-fa-list-alt"></i>
                <span class="phpdebugbar-text">Dumped-data</span>
                <span class="phpdebugbar-badge"></span>
            </a>
        </div>

        <div class="phpdebugbar-header-right">
            <a class="phpdebugbar-close-btn"></a>
            <a class="phpdebugbar-minimize-btn"></a>
            <a class="phpdebugbar-maximize-btn"></a>
            <span class="phpdebugbar-indicator"><i class="phpdebugbar-fa phpdebugbar-fa-code"></i>
                <span class="phpdebugbar-text"><?= phpversion() ?>></span>
                <span class="phpdebugbar-tooltip">PHP Version</span>
            </span>
            <span class="phpdebugbar-indicator">
                <i class="phpdebugbar-fa phpdebugbar-fa-clock-o"></i>
                <span class="phpdebugbar-text"><?= $total_t ?>ms</span>
                <span class="phpdebugbar-tooltip">Request Duration</span>
            </span>
            <span class="phpdebugbar-indicator">
                <i class="phpdebugbar-fa phpdebugbar-fa-cogs"></i>
                <span class="phpdebugbar-text"><?= size_format($__memory) ?></span>
                <span class="phpdebugbar-tooltip">Memory Usage</span>
            </span>
            <span class="phpdebugbar-indicator">
                <i class="phpdebugbar-fa phpdebugbar-fa-share"></i>
                <span class="phpdebugbar-text"><?= $__route['uri'] ?></span>
                <span class="phpdebugbar-tooltip">Route</span>
            </span>
        </div>
    </div>

    <div class="phpdebugbar-body">

        <!-- dump-data -->
        <div class="phpdebugbar-panel js-dump-data phpdebugbar-active">
            <div class="phpdebugbar-dump-console" style="height: 100% !important;"><?= $__DEBUG_DATA ?></div>
        </div>
        <!-- timeline-data -->
        <div class="phpdebugbar-panel js-timeline-data">
            <ul class="phpdebugbar-widgets-timeline">
                <li>
                    <table class="phpdebugbar-widgets-params phpdebugbar-timeline-table">
                        <tr>
                            <td class="phpdebugbar-widgets-name">Booting - <?= $boot_t ?>ms</td>
                            <td class="phpdebugbar-widgets-value">
                                <div class="phpdebugbar-widgets-measure">
                                    <span class="phpdebugbar-widgets-value percentage" style="width:<?= $boot_p ?>%"></span>
                                    <span class="phpdebugbar-widgets-label"><?= $boot_p ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="phpdebugbar-widgets-name">Application - <?= $app_t ?>ms</td>
                            <td class="phpdebugbar-widgets-value">
                                <div class="phpdebugbar-widgets-measure">
                                    <span class="phpdebugbar-widgets-value percentage" style="width:<?= $app_p ?>%"></span>
                                    <span class="phpdebugbar-widgets-label"><?= $app_p ?>%</span>
                                </div>
                            </td>
                        </tr>
                    </table>
                </li>
            </ul>
        </div>
        <!-- view-data -->
        <div class="phpdebugbar-panel js-view-data">
            <div class="phpdebugbar-widgets-templates">
                <div class="phpdebugbar-widgets-status"><span><?= count($__view) ?> templates were rendered</span></div>
                <ul class="phpdebugbar-widgets-list">
                    <?php
                    foreach ($__view as $item) {
                        ?>
                        <li class="phpdebugbar-widgets-list-item">
                            <span class="phpdebugbar-widgets-name"><?= realpath($item) ?></span>
                            <!--
                            <span title="Parameter count" class="phpdebugbar-widgets-param-count">0</span>
                            <span title="Type" class="phpdebugbar-widgets-type">blade</span>
                            <a href="phpstorm://open?file=/app/resources/views/index.blade.php&amp;line=0" class="phpdebugbar-widgets-editor-link">file</a>
                            -->
                        </li>
                        <?php
                    }
                    ?>
                </ul>
                <div class="phpdebugbar-widgets-callgraph"></div>
            </div>
        </div>
        <!-- route-data -->
        <div class="phpdebugbar-panel js-route-data">
            <dl class="phpdebugbar-widgets-kvlist phpdebugbar-widgets-htmlvarlist">
                <?php
                foreach ($__route as $key => $item) {
                    ?>
                    <dt class="phpdebugbar-widgets-key"><span title="<?= $key ?>"><?= $key ?></span></dt>
                    <dd class="phpdebugbar-widgets-value"><?= is_string($item) ? $item : json_encode($item) ?></dd>
                    <?php
                }
                ?>
            </dl>
        </div>
        <!-- sql-data -->
        <div class="phpdebugbar-panel js-sql-data">
            <div class="phpdebugbar-widgets-sqlqueries">
                <div class="phpdebugbar-widgets-status"><span><?= count($__sql) ?> statements were executed</span><span
                            title="Accumulated duration" class="phpdebugbar-widgets-duration"><?= $sql_total_t ?>μs</span></div>
                <div class="phpdebugbar-widgets-toolbar"></div>
                <ul class="phpdebugbar-widgets-list">
                    <?php
                    $caller = '';
                    foreach ($__sql as $item) {
                        foreach ($item['backtrace'] as $b) {
                            if (isset($b['file']) && strrpos($b['file'], 'Driver.php') === false) {
                                $caller = $b['file'];
                                break;
                            }
                        }
                        ?>
                        <li class="phpdebugbar-widgets-list-item <?= $item['status'] ?>" data-connection="migration" title="<?= $item['label'] ?>"><!--
                            --><code class="phpdebugbar-widgets-sql"><span class="hljs-operator"><?= $item['sql'] ?></span></code>
                            <span title="Duration" class="phpdebugbar-widgets-duration"><?= round(($item['sqlTimeFinish'] - $item['sqlTimeStart']) * 1000, 2); ?>ms</span>
                            <span title="Backtrace" class="phpdebugbar-widgets-stmt-id"><?= $caller ?></span>
                            <span title="Connection" class="phpdebugbar-widgets-database"><?= $item['connection'] ?></span>
                            <span title="Driver" class="phpdebugbar-widgets-database"><?= $item['driver'] ?></span><!--
                        --></li>
                        <?php
                    }
                    ?>
                </ul>
            </div>
        </div>
        <!-- session-data -->
        <div class="phpdebugbar-panel js-session-data">
            <dl class="phpdebugbar-widgets-kvlist phpdebugbar-widgets-varlist">
                <?php
                foreach ($__session as $key => $item) {
                    ?>
                    <dt class="phpdebugbar-widgets-key"><span title="<?= $key ?>"><?= $key ?></span></dt>
                    <dd class="phpdebugbar-widgets-value">
                        <?= dumpIntoStr($item) ?>
                    </dd>
                    <?php
                }
                ?>
            </dl>
        </div>
        <!-- request-data -->
        <div class="phpdebugbar-panel js-request-data">
            <dl class="phpdebugbar-widgets-kvlist phpdebugbar-widgets-varlist">

                <dt class="phpdebugbar-widgets-key"><span title="UserAgent">UserAgent:</span></dt>
                <dd class="phpdebugbar-widgets-value">
                    <?= \Core\App::$request->userAgent() ?>
                </dd>

                <dt class="phpdebugbar-widgets-key"><span title="IP">Remote IP:</span></dt>
                <dd class="phpdebugbar-widgets-value">
                    <?= \Core\App::$request->ip() ?>
                </dd>

                <dt class="phpdebugbar-widgets-key"><span title="Host">Host:</span></dt>
                <dd class="phpdebugbar-widgets-value">
                    <?= \Core\App::$request->host() ?>
                </dd>

                <dt class="phpdebugbar-widgets-key"><span title="Port">Port:</span></dt>
                <dd class="phpdebugbar-widgets-value">
                    <?= \Core\App::$request->port() ?>
                </dd>

                <dt class="phpdebugbar-widgets-key"><span title="Method">Method:</span></dt>
                <dd class="phpdebugbar-widgets-value">
                    <?= \Core\App::$request->method() ?>
                </dd>

                <?php
                foreach (\Core\App::$request->server() as $key => $item) {
                    ?>
                    <dt class="phpdebugbar-widgets-key"><span title="<?= $key ?>">SERVER[<?= $key ?>]</span></dt>
                    <dd class="phpdebugbar-widgets-value">
                        <?= dumpIntoStr($item) ?>
                    </dd>
                    <?php
                }

                foreach (\Core\App::$request->header() as $key => $item) {
                    ?>
                    <dt class="phpdebugbar-widgets-key"><span title="<?= $key ?>">HEADERS[<?= $key ?>]</span></dt>
                    <dd class="phpdebugbar-widgets-value">
                        <?= dumpIntoStr($item) ?>
                    </dd>
                    <?php
                }

                foreach (\Core\App::$request->get() as $key => $item) {
                    ?>
                    <dt class="phpdebugbar-widgets-key"><span title="<?= $key ?>">_GET[<?= $key ?>]</span></dt>
                    <dd class="phpdebugbar-widgets-value">
                        <?= dumpIntoStr($item) ?>
                    </dd>
                    <?php
                }

                foreach (\Core\App::$request->post() as $key => $item) {
                    ?>
                    <dt class="phpdebugbar-widgets-key"><span title="<?= $key ?>">_POST[<?= $key ?>]</span></dt>
                    <dd class="phpdebugbar-widgets-value">
                        <?= dumpIntoStr($item) ?>
                    </dd>
                    <?php
                }

                foreach (\Core\App::$request->file() as $key => $item) {
                    ?>
                    <dt class="phpdebugbar-widgets-key"><span title="<?= $key ?>">_FILE[<?= $key ?>]</span></dt>
                    <dd class="phpdebugbar-widgets-value">
                        <?= dumpIntoStr($item) ?>
                    </dd>
                    <?php
                }

                foreach (\Core\App::$request->cookie() as $key => $item) {
                    ?>
                    <dt class="phpdebugbar-widgets-key"><span title="<?= $key ?>">_COOKIE[<?= $key ?>]</span></dt>
                    <dd class="phpdebugbar-widgets-value">
                        <?= dumpIntoStr($item) ?>
                    </dd>
                    <?php
                }
                ?>
            </dl>
        </div>
        <!-- response-data -->
        <div class="phpdebugbar-panel js-response-data">
            <dl class="phpdebugbar-widgets-kvlist phpdebugbar-widgets-varlist">

                <?php
                if (function_exists('headers_list')) {
                    foreach (headers_list() as $key => $item) {
                        ?>
                        <dt class="phpdebugbar-widgets-key"><span title="<?= $key ?>">HEADER[<?= $key ?>]</span></dt>
                        <dd class="phpdebugbar-widgets-value"><?= $item ?></dd>
                    <?php
                    }
                }
                ?>

            </dl>
        </div>
    </div>
    <a class="phpdebugbar-restore-btn">Debug</a>
</div>