<?php
/**
 * PhileCMS Sub Navigation Plugin
 *
 * @package gibbs\phileSubNavigation
 * @author  Dan Gibbs <daniel.gibbs@gmail.com>
 * @license MIT
 */
namespace Phile\Plugin\Gibbs\phileSubNavigation;

class Plugin extends \Phile\Plugin\AbstractPlugin implements
    \Phile\Gateway\EventObserverInterface
{
    /**
     * Register plugin events via the constructor
     *
     * @return void
     */
    public function __construct()
    {
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
        if($eventKey == 'template_engine_registered')
        {
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
            // Convert index files
            // @FIXME: Should this be optional?
            $uri   = str_replace('index', '/', $page->getUrl());

            $parts = array_filter( explode('/', $uri) );

            // Skip excluded pages
            if( !is_array($parts) )
                continue;

            if( isset($parts[0]) 
                AND in_array($parts[0], $this->settings['exclude']) )
                continue;

            $list = array();

            foreach ( array_reverse($parts) as $key => $part ) {
                $list = array($part => array('children' => $list));

                // Add meta data to end of array
                if(end($parts) == $part) {
                    $uri = implode('/', $parts);

                    $list[$part] = array(
                        'meta' => $page->getMeta(),
                        'uri'  => $uri,
                        'url'  => \Phile\Utility::getBaseUrl()  . '/' . $uri,
                    );
                }
            }

            $hierarchy = array_merge_recursive($hierarchy, $list);
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
