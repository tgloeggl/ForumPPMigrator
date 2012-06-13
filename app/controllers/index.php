<?php

/*
 * Copyright (C) 2011 - Till Glöggler     <tgloeggl@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */


/**
 * @author    tgloeggl@uos.de
 * @copyright (c) Authors
 */

//require_once ( "sphinxapi.php" );
require_once 'app/controllers/studip_controller.php';
require_once $this->trails_root .'/models/ForumPPMigrator.php';

class IndexController extends StudipController
{

    function index_action()
    {
        Navigation::activateItem('course/forum2/migration');

        $this->seminar_id = $this->getId();
    }
    
    function ajax_search_action()
    {
        $this->layout = null;
        if (strlen(Request::get('search_term')) > 3) {
            $this->seminars = ForumPPMigrator::getSeminars(Request::get('search_term'));
        }
        
        $this->seminar_id = $this->getId();
    }
    
    function migrate_action()
    {
        $sm = Request::optionArray('area');
        ForumPPMigrator::migrate($this->getId(), Request::option('seminar_id'), $sm[0], Request::get('areaname'));
        
        $this->redirect(PluginEngine::getLink('/forumpp/index'));
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    /* * * * * H E L P E R   F U N C T I O N S * * * * */
    /* * * * * * * * * * * * * * * * * * * * * * * * * */
    function getId()
    {
        if (!Request::option('cid')) {
            if ($GLOBALS['SessionSeminar']) {
                URLHelper::bindLinkParam('cid', $GLOBALS['SessionSeminar']);
                return $GLOBALS['SessionSeminar'];
            }

            return false;
        }

        return Request::option('cid');
    }

    /**
     * Common code for all actions: set default layout and page title.
     *
     * @param type $action
     * @param type $args
     */
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        // set correct encoding if this is an ajax-call
        if (Request::isAjax()) {
            header('Content-Type: text/html; charset=Windows-1252');
        }
        
        $this->flash = Trails_Flash::instance();

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);

        PageLayout::setTitle(getHeaderLine($this->getId()) .' - '. _('Forum'));
    }
}
