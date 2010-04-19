<?php

/**
 * "ymF" namespace configuration options
 *
 * @copyright 2010 Misha Yurasov
 * @package ymF
 */

namespace ymF;

use ymF\ConfigBase;

class Config extends ConfigBase
{
  protected static $options = array(

    // Project name
    'project_name' => \PROJECT_NAME,

    // Project version (major.minor<.change> <status>)
    'project_version' => '0.0 dev',

    // Namespace for project models (relative to project_name)
    // For empty namespace set to NULL
    'models_namespace' => 'Models',

    // Namespace for project controllers (relative to project_name)
    // For empty namespace set to NULL
    'controllers_namespace' => 'Controllers',

    // <editor-fold defaultstate="collapsed" desc="Libraries">

    /**
     * Paths to libraries
     * If path begins with "/" is is threated as absolute
     * otherwise, it is relative to ymF\PATH_LIBRARIES
     */

    'libraries' => array(
        'Twig'        => 'Twig/lib/Twig',
        'Doctrine'    => '/Projects/_libraries/Doctrine-1.2.2/lib',
        'Doctrine_2'  => '/Projects/_libraries/DoctrineORM-2.0.0-ALPHA4/DoctrineORM-2.0.0'
    ),

    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="RequestHandler">

    'RequestHandler' => array(

    // Default renderer class
        'default_renderer' => 'Twig',

        // Parameter names for calling a controller from web

        'get_args' => array(
            'controller'  => '_c',
            'method'      => '_m',
            'renderer'    => '_r'
        )

    ),

    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="PDOService">

    'PDOService' => array(

        'dsn' => 'mysql:dbname=test;host=127.0.0.1',

        'user' => 'username',

        'password' => 'passowrd',

        // PDO driver options
        'options' => array()

    ),

    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="TwigService">

    'TwigService' => array(

    // Directory under PATH_TEMPLATES for twig templates
        'templates_dir' => '',

        /*
       * Directory under PATH_TEMP for compiled templates cache
       * OR
       * false (no caching)
       * OR
       * null (cache in system temp directory)
        */
        'cache' => 'TwigCache',

        // Enable Twig_Extension_Escaper and turn auto-escaping mode on
        'autoescaping' => false,

        // Options of Twig_Enviroment class

        'options' => array(
            'debug'               => false,
            'trim_blocks'         => false,
            'charset'             => 'utf-8',
            'base_template_class' => 'Twig_Template',
            'auto_reload'         => true
        )
    ),

    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="MySQLiService">

    'MySQLiService' => array(
        'host' => 'localhost',
        'user' => 'username',
        'password' => 'password',
        'database' => 'test'
    )

    // </editor-fold>

  );
}