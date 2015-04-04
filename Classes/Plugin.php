<?php
/**
 * PhileCMS Sub Navigation Plugin
 *
 * @package gibbs\phileSubNavigation
 * @author  Dan Gibbs <daniel.gibbs@gmail.com>
 */
namespace Phile\Plugin\Gibbs\phileSubNavigation;

class Plugin extends \Phile\Plugin\AbstractPlugin implements
    \Phile\Gateway\EventObserverInterface
{
    protected $current_path = null;
    protected $current_host = null;

    /**
     * Register plugin events via the constructor
     *
     * @return void
     */
    public function __construct()
    {
        \Phile\Event::registerEvent('request_uri', $this);
        \Phile\Event::registerEvent('template_engine_registered', $this);
    }

    /**
     * Listen to event triggers
     *
     * @param  string  $eventKey  Triggered event key
     * @param  array   $data      Array of event data
     * @return void    
     */
    public function on($eventKey, $data = null)
    {
        if($eventKey == 'request_uri')
        {
            // Set the current path
            $this->current_path = $data['uri'];
        }

        if($eventKey == 'template_engine_registered')
        {
            //print_r($data); die();
            $this->current_host = $data['data']['base_url'];
            $tree = null;

            $pagesRepository = new \Phile\Repository\Page();
            $pages = $pagesRepository->findAll(\Phile\Registry::get('Phile_Settings'));

            // Caching Disabled
            if($this->settings['cache'] !== true) {
                $tree = $this->generateTree($pages);
            }
            // Caching Enabled
            else {
                // Try and get a cached version
                $tree = $this->getCache(CONTENT_DIR);

                if($tree === false OR !is_array($tree) ) {
                    $tree = $this->generateTree($pages);

                    // Cache the current tree
                    $this->setCache(CONTENT_DIR, $tree);
                }
            }

            // @TODO Remove this
            if($this->settings['print'] === true)
                print_r($tree);

            // Set the navigation array for the template engine
            $data['data']['navigation'] = $tree;
        }
    }

    /**
     * Generate a hierarchical array based on a content directory
     *
     * @param   array   $pages      An array of \Phile\Repository\Page objects
     * @return  array   $hierarchy  The hierarchy array
     */
    protected function generateTree($pages)
    {
        $hierarchy = array();

        foreach($pages as $page) {
            // The page path
            $page_path = str_replace('index', '/', $page->getUrl());

            // An array of the page path
            $path_components = array_filter( explode('/', $page_path) );

            // Skip the homepage
            if( !is_array($path_components) )
                continue;

            // Skip excluded pages
            if( isset($path_components[0]) 
                AND in_array($path_components[0], $this->settings['exclude']) )
                continue;

            // Current page hierarchy
            $list   = array();
            $depth  = sizeof($path_components);
            $active = false;

            // Start from the last child and work up
            foreach( array_reverse($path_components) as $child ) {

                // Add meta data to the last child
                if(end($path_components) == $child) {

                    // Active page
                    $active = $this->current_path == $page_path ? true : false;

                    if($depth == 1)
                        $parent = false;
                    else
                        $parent = str_replace('/' . $child, '', $page_path);

                    $list[$child] = array(
                        'active'  => $active,
                        'level'   => $depth,
                        'meta'    => $page->getMeta(),
                        'path'    => $page_path,
                        'parent'  => $parent, 
                        'uri'     => $page_path,
                        'url'     => $this->current_host . '/' . $page_path,
                    );
                }
                // Move children underneath parents
                else {
                    $list = array($child => array('children' => $list));
                }

                // Decrement current level/depth
                $depth = $depth--;
            }

            // Merge
            $hierarchy = array_replace_recursive($list, $hierarchy);
        }

        return $hierarchy;
    }

    /**
     * Return a cached hierachy array
     *
     * @param   string       $directory  The content directory
     * @return  array|bool   Returns a cached hierarchy array or false
     */
    protected function getCache($directory = null)
    {
        $storage = \Phile\ServiceLocator::getService('Phile_Data_Persistence');

        if($storage->has('hierarchy_modified')) {

            if($storage->get('hierarchy_modified') != filemtime($directory . '/.'))
                return false;
            else
                return $storage->get('hierarchy_object');
        }

        return false;
    }

    /**
     * Cache a generated hierarchy
     * 
     * @param   string        $directory  The content directory
     * @param   array|string  $data       Data to cache
     * @return  void
     */
    protected function setCache($directory = null, $data = null)
    {
        $storage = \Phile\ServiceLocator::getService('Phile_Data_Persistence');

        $storage->set('hierarchy_modified', filemtime($directory . '/.'));
        $storage->set('hierarchy_object', $data);
    }
}
