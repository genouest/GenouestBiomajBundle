Overview
========

This bundle allows you to use the BioMAJ REST API from a Symfony application.
For more information on BioMAJ, see http://biomaj.genouest.org/.

How does it work?
-----------------

The bundle contains some classes to easily retrieve informations about the banks managed by BioMAJ from your PHP code.
You can request informations based on bank types, bank file format and/or bank names.
For example, you can get the list of all the files in fasta format from the latest release of the banks of nucleic type.
This bundle also comes with all you need to create a choice widget containing a list of bank files in a form.

Installation
------------

You need to have BioMAJ server installed and properly configured.

Checkout a copy of the bundle code:

.. code-block:: bash

    git submodule add git@github.com:genouest/GenouestBiomajBundle.git vendor/bundles/Genouest/Bundle/BiomajBundle
    
Then register the bundle with your kernel:

.. code-block:: php

    // in AppKernel::registerBundles()
    $bundles = array(
        // ...
        new Genouest\Bundle\BiomajBundle\GenouestBiomajBundle(),
        // ...
    );

Make sure that you also register the namespaces with the autoloader:

.. code-block:: php

    // app/autoload.php
    $loader->registerNamespaces(array(
        // ...
        'Genouest\\Bundle' => __DIR__.'/../vendor/bundles',
        // ...
    ));

Import the routes defined in the bundle.

.. code-block:: yaml

    // app/config/routing.yml
    // ...
    _biomaj:
        resource: "@GenouestBiomajBundle/Controller/BiomajController.php"
        prefix: /biomaj
        type: annotation
    // ...
    
Publish the assets in the web dir:

.. code-block:: bash

    app/console assets:install --symlink web/


Configuration
-------------

The following configuration keys are available (with their default values):

.. code-block:: yaml

    # app/config/config.yml
    genouest_biomaj:
        # The BioMAJ server url
        server:         "http://www.example.org/BmajWatcher"

Usage
-----

Using the API
~~~~~~~~~~~~~

You can directly request a BioMAJ server using the API provided by this bundle. The first step is to get a Genouest\Bundle\BiomajBundle\Biomaj\BankManager instance.
You can get it with the 'biomaj.bank.manager' service:

.. code-block:: php

    $bankManager = $this->container->get('biomaj.bank.manager');

Have a look at the code in the 'Biomaj' subdir to find what you can do with this API.
Note that the performance of this API depends on the performances (and availability) of the BioMAJ server as it uses the REST API.


Creating a choice widget
~~~~~~~~~~~~~~~~~~~~~~~~

Suppose you want to add a select box to a form containing the list of all the fasta files of all the nucleic banks.
The first step is to add the corresponding choice field in your form:

.. code-block:: php

    $builder->add('dbPath', 'choice', array('choices' => $fastaList));

You can generate the $fastaList using the BankManager API.

.. code-block:: php

    $bankManager = $this->container->get('biomaj.bank.manager');
    $fastaList = $bankManager->getJsonBankList(array('nucleic'), 'fasta', true);

Set to true the last argument of getJsonBankList() if you want the bank names to be cleaned up (e.g. 'my_bank' => 'My bank').


In your form model, you want to add a constraint on the dbPath field to be sure the selected bank is valid.
To do so, use the Biomaj constraint included in this bundle:

.. code-block:: php

    /**
     * @Genouest\Bundle\BiomajBundle\Constraints\Biomaj(type = {"nucleic"}, format = "fasta", cleanup = true)
     */
    public $dbPath;


Improving the widget performances
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you use the choice widget with the procedure describe above, you may find that your form gets much slower.
Don't worry, you can speed it up.

1. Faster form loading
When building the form, you can use the BankManager and use the results when adding the widget:

.. code-block:: php

    $builder->add('dbPath', 'choice', array('choices' => $fastaList));

You can also simply give a blank array() of choices and use an AJAX request to load the list of banks on the client side, when the page is loaded.

.. code-block:: php

    $builder->add('dbPath', 'choice', array('choices' => array()));

In your template where the form is displayed, just add some code like this (twig):

.. code-block:: jinja

    {% include 'GenouestBiomajBundle::js.html.twig' %}
    <script type="text/javascript">
        //<![CDATA[
        
        function updateDbList() {
            reloadBiomajDbList('#yourForm_dbPath', 'nucleic', 'blast', 'false', 'true');
        }
        jQuery(document).ready(updateDbList);

        //]]>
    </script>

2. Faster form validation
By default, the BiomajValidator retrieve the list of allowed bank files from the BioMAJ server when the user submit the form.
Another validator, called BiomajPrefixValidator, is available. With this validator, the submitted value is only compared to a specified prefix.
For example, if you're sure all the allowed files are in /db/, you can use the BiomajPrefix constraint like this:

.. code-block:: php

    /**
     * @Genouest\Bundle\BiomajBundle\Constraints\BiomajPrefix(prefix = "/db/")
     */
    public $dbPath;

This will be much faster because no REST request is done by the validator.
Of course, before using this validator, check that no sensible file is present in the prefix directory.
The path is normalized ( '..' are resolved, ...) before validation and the existence of the file is checked too.


Route
~~~~~

This bundle comes with one route named ``_biomaj_dblist``. It is used for AJAX requests.

