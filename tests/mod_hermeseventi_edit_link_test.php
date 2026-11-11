<?php
define('_JEXEC', 1);
require dirname(__DIR__) . '/modules/mod_hermeseventi/helper.php';
$checks = 0;
$check = function ($condition, $message) use (&$checks) {
    if (!$condition) throw new RuntimeException($message);
    $checks++;
};
$modules = [(object) ['id' => 125, 'title' => 'Event manager', 'position' => 'hermes_manager']];
foreach ([
    '<p>{loadmodule mod_hermesmanager}</p>',
    '{loadmodule hermesmanager}',
    '{loadmodule mod_hermesmanager, Event manager}',
    '{loadmodule mod_hermesmanager, Event manager, none}',
    '{loadmoduleid 125}',
    '{loadposition hermes_manager}',
    '{loadposition hermes_manager, none}',
] as $article) {
    $check(ModHermesEventiHelper::contieneManager($article, $modules), 'Recognize supported Joomla module inclusion');
}
foreach ([
    '{loadmodule mod_hermesmanager, Another title}',
    '{loadmodule mod_hermesmanager_other}',
    '{loadmoduleid 1250}',
    '{loadmoduleid 124}',
    '{loadposition another_position}',
    '<p>An article without an event manager.</p>',
] as $article) {
    $check(!ModHermesEventiHelper::contieneManager($article, $modules), 'Do not link to an unrelated article');
}
$check(!ModHermesEventiHelper::contieneManager('{loadmodule mod_hermesmanager}', []), 'Published and accessible manager is required');
echo "Passed: $checks manager destination checks.\n";
