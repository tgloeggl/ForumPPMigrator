<?php

/**
 * ForumPPMigrator.php - Short description for ForumPPMigrator
 *
 * Long description for file (if any)...
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 * @category    Stud.IP
 */

class ForumPPMigrator {
    static function getList($seminar_id, $get_childs = true)
    {
        $ret = array();
        
        $stmt = DBManager::get()->prepare("SELECT * FROM px_topics
            WHERE Seminar_id = ? AND topic_id = root_id
            ORDER BY mkdate ASC");
        $stmt->execute(array($seminar_id, $parent_id));
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // set depth-level
            $data['level'] = 0;
            $ret[] = $data;
            
            if ($get_childs) {
                // get childs
                $childs = self::getChilds($seminar_id, $data['topic_id']);

                if (!empty($childs)) {
                    $ret = array_merge($ret, $childs);
                }
            }
        }
        
        return $ret;
    }

    static function getEntries($seminar_id, $parent_id)
    {
        $stmt = DBManager::get()->prepare("SELECT * FROM px_topics
            WHERE Seminar_id = ? AND parent_id = ?
            ORDER BY mkdate ASC");
        $stmt->execute(array($seminar_id, $parent_id));
        
        return $stmt->fetchAll();
    }

    static function getChilds($seminar_id, $parent_id, $level = 1)
    {
        $ret = array();
        
        $stmt = DBManager::get()->prepare("SELECT * FROM px_topics
            WHERE Seminar_id = ? AND parent_id = ?
            ORDER BY mkdate ASC");
        $stmt->execute(array($seminar_id, $parent_id));
        
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // set depth-level
            $data['level'] = $level;
            $ret[] = $data;
            
            // get childs
            $childs = self::getChilds($seminar_id, $data['topic_id'], $level + 1);
            
            if (!empty($childs)) {
                $ret = array_merge($ret, $childs);
            }
        }
        
        return $ret;
    }
    
    static function migrate($seminar_id, $from_seminar, $sm, $areaname = null)
    {
        if ($sm == 'single') {
            $list = self::flattenList(self::getList($from_seminar));

            $new_id = md5(uniqid(rand()));

            self::insert(array(
                'topic_id'    => $new_id,
                'seminar_id'  => $seminar_id,
                'user_id'     => $GLOBALS['user']->id,
                'name'        => $areaname,
                'content'     => '',
                'author'      => get_fullname($GLOBALS['user']->id),
                'author_host' => getenv('REMOTE_ADDR'),
                'mkdate'      => time(),
                'chdate'      => time()
            ), $seminar_id);

            foreach ($list as $element) {
                self::insert(array(
                    'topic_id'    => $element['topic_id'],
                    'seminar_id'  => $seminar_id,
                    'user_id'     => $element['user_id'],
                    'name'        => $element['name'],
                    'content'     => $element['description'],
                    'author'      => $element['author'],
                    'author_host' => $element['author_host'],
                    'mkdate'      => $element['mkdate'],
                    'chdate'      => $element['chdate']
                ), $new_id);

                if (!empty($element['childs'])) foreach ($element['childs'] as $child) {
                    self::insert(array(
                        'topic_id'    => $child['topic_id'],
                        'seminar_id'  => $seminar_id,
                        'user_id'     => $child['user_id'],
                        'name'        => $child['name'],
                        'content'     => $child['description'],
                        'author'      => $child['author'],
                        'author_host' => $child['author_host'],
                        'mkdate'      => $child['mkdate'],
                        'chdate'      => $child['chdate']
                    ), $element['topic_id']);
                }
            }
        } elseif ($sm == 'multi') {
            foreach (self::getList($from_seminar, false) as $element) {
                self::insert(array(
                    'topic_id'    => $element['topic_id'],
                    'seminar_id'  => $seminar_id,
                    'user_id'     => $element['user_id'],
                    'name'        => $element['name'],
                    'content'     => $element['description'],
                    'author'      => $element['author'],
                    'author_host' => $element['author_host'],
                    'mkdate'      => $element['mkdate'],
                    'chdate'      => $element['chdate']
                ), $seminar_id);

                //echo $element['name'] . '<br>';
                
                foreach (self::getEntries($from_seminar, $element['topic_id']) as $child1) {
                    self::insert(array(
                        'topic_id'    => $child1['topic_id'],
                        'seminar_id'  => $seminar_id,
                        'user_id'     => $child1['user_id'],
                        'name'        => $child1['name'],
                        'content'     => $child1['description'],
                        'author'      => $child1['author'],
                        'author_host' => $child1['author_host'],
                        'mkdate'      => $child1['mkdate'],
                        'chdate'      => $child1['chdate']
                    ), $element['topic_id']);

                    //echo '&bullet; ' . $child1['name'] . '<br>';
                    foreach(self::getChilds($from_seminar, $child1['topic_id']) as $child2) {
                        self::insert(array(
                            'topic_id'    => $child2['topic_id'],
                            'seminar_id'  => $seminar_id,
                            'user_id'     => $child2['user_id'],
                            'name'        => $child2['name'],
                            'content'     => $child2['description'],
                            'author'      => $child2['author'],
                            'author_host' => $child2['author_host'],
                            'mkdate'      => $child2['mkdate'],
                            'chdate'      => $child2['chdate']
                        ), $child1['topic_id']);
                        
                        //echo '&bullet; &bullet;' . $child2['name'] . '<br>';
                    }
                }
            }
        }
    }
    
   
    static function flattenList($list)
    {
        $new_list = array();
        $zw = array();

        foreach ($list as $element) {
            if ($element['level'] == 0) {
                if (!empty($zw)) {
                    $new_list[] = $zw;
                    $zw = array();
                }
                
                $zw = $element;
            } else {
                $zw['childs'][] = $element;
            }
        }
        
        if (!empty($zw)) {
            $new_list[] = $zw;
        }
        
        return $new_list;
    }

    static function insert($data, $parent_id) {
        $constraint = self::getConstraints($parent_id);

        // #TODO: Zusammenfassen in eine Transaktion!!!
        DBManager::get()->exec('UPDATE forumpp_entries SET lft = lft + 2
            WHERE lft > '. $constraint['rgt'] ." AND seminar_id = '". $constraint['seminar_id'] ."'");
        DBManager::get()->exec('UPDATE forumpp_entries SET rgt = rgt + 2
            WHERE rgt >= '. $constraint['rgt'] ." AND seminar_id = '". $constraint['seminar_id'] ."'");

        $stmt = DBManager::get()->prepare("INSERT INTO forumpp_entries
            (topic_id, seminar_id, user_id, name, content, mkdate, chdate, author,
                author_host, lft, rgt, depth, anonymous)
            VALUES (? ,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(array($data['topic_id'], $data['seminar_id'], $data['user_id'],
            $data['name'], $data['content'], $data['mkdate'], $data['chdate'], $data['author'], $data['author_host'],
            $constraint['rgt'], $constraint['rgt'] + 1, $constraint['depth'] + 1, 0));
    }
    
    static function addArea($category_id, $area_id) {
        // remove area from all other categories
        $stmt = DBManager::get()->prepare("DELETE FROM
            forumpp_categories_entries
            WHERE topic_id = ?");
        $stmt->execute(array($area_id));

        // add area to this category, make sure it is at the end
        $stmt = DBManager::get()->prepare("SELECT COUNT(*) FROM
            forumpp_categories_entries
            WHERE category_id = ?");
        $stmt->execute(array($category_id));
        $new_pos = $stmt->fetchColumn() + 1;

        $stmt = DBManager::get()->prepare("REPLACE INTO
            forumpp_categories_entries
            (category_id, topic_id, pos) VALUES (?, ?, ?)");
        $stmt->execute(array($category_id, $area_id, $new_pos));
    }

    static function getConstraints($topic_id)
    {
        // look up the range of postings
        $range_stmt = DBManager::get()->prepare("SELECT *
            FROM forumpp_entries WHERE topic_id = ?");
        $range_stmt->execute(array($topic_id));
        if (!$data = $range_stmt->fetch(PDO::FETCH_ASSOC)) {
            return false;
            // throw new Exception("Could not find entry with id >>$topic_id<< in forumpp_entries, " . __FILE__ . " on line " . __LINE__);
        }
        
        if ($data['depth'] == 1) {
            $data['area'] = 1;
        }

        return $data;
    }    
    
    static function getSeminars($search_term)
    {
        $stmt = DBManager::get()->prepare("SELECT Seminar_id, Name FROM seminare
            WHERE Name LIKE ?");
        $stmt->execute(array('%' . $search_term .'%'));
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}