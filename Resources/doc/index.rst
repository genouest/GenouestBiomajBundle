========
Overview
========

This bundle allows you to use the BioMAJ REST API.


How does it work?
-----------------


Installation
------------

You need to have BioMAJ server installed and properly configured.

Checkout a copy of the bundle code::

    git submodule add gitolite@chili.genouest.org:sf2-biomajbundle vendor/bundles/Genouest/Bundle/BiomajBundle
    
Then register the bundle with your kernel::

    // in AppKernel::registerBundles()
    $bundles = array(
        // ...
        new Genouest\Bundle\BiomajBundle\GenouestBiomajBundle(),
        // ...
    );

Make sure that you also register the namespaces with the autoloader::

    // app/autoload.php
    $loader->registerNamespaces(array(
        // ...
        'Genouest\\Bundle' => __DIR__.'/../vendor/bundles',
        // ...
    ));

Finally, import the routes defined in the bundle.

    // app/config/routing.yml
    // ...
    _blast:
        resource: "@GenouestBiomajBundle/Controller/BiomajController.php"
        type: annotation
    // ...


Configuration
-------------

The following configuration keys are available (with their default values)::

    # app/config/config.yml
    genouest_biomaj:
        # The BioMAJ server url
        server:         "http://www.example.org/BmajWatcher"

Customization
-------------

