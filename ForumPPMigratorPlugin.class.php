<?php
/*
 * ForumPPMigratorPlugin.class.php - ForumPPMigratorPlugin
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <till.gloeggler@elan-ev.de>
 * @copyright   2011 ELAN e.V. <http://www.elan-ev.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

require_once 'vendor/trails/trails.php';

class ForumPPMigratorPlugin extends StudipPlugin implements StandardPlugin
{

    /**
     * Initialize a new instance of the plugin.
     */
    function __construct()
    {
        parent::__construct();

        // do nothing if plugin is deactivated in this seminar/institute
        if (!PluginManager::isPluginActivated($this->getPluginId(), Request::get('cid', $GLOBALS['SessSemName'][1]))) return;
        
        // if there is no forum2-navigation, quit
        if (!Navigation::hasItem('course/forum2')) return;

        // add navigation for migrator
        $navigation = Navigation::getItem('course/forum2');
        $navigation->addSubNavigation('migration', new Navigation(_("Migration"), PluginEngine::getLink('forumppmigratorplugin/index')));
    }

  
    /**
     * This method dispatches all actions.
     *
     * @param string   part of the dispatch path that was not consumed
     */
    function perform($unconsumed_path)
    {
        $trails_root = $this->getPluginPath();
        $dispatcher = new Trails_Dispatcher($trails_root .'/app', PluginEngine::getUrl('forumpp/index'), 'index');
        $dispatcher->dispatch($unconsumed_path);

    }    

    // implement interface methods
    function getIconNavigation($course_id, $last_visit)
    {
        return null;
    }

    function getInfoTemplate($course_id)
    {
        return null;
    }
}
