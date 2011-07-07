<?php

namespace Genouest\Bundle\BiomajBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Genouest\Bundle\DrmaaBundle\Entity\Job;
use Genouest\Bundle\BiomajBundle\Biomaj\BankManagerAccessor;

class BiomajController extends Controller
{

    /**
     * Get JSON list of banks from biomaj server
     *
     * @Route("/dblist/{dbtype}/{dbformat}/{filterall}/{cleanup}", name = "_biomaj_dblist", defaults = {"dbtype" = "all", "dbformat" = "all", "filterall" = false, "cleanup" = false})
     * @Template()
     *
     * @param $dbtype string The list of dbtypes to retrieve, separated by '|', '/' must be replaced by '___'
     * @param $dbformat string The bank format to retrieve
     * @param $filterall bool Should 'All' lies be filtered
     * @param $cleanup bool Should db names be cleanup for better display
     */
    public function dbListAction($dbtype, $dbformat, $filterall, $cleanup)
    {
        $dbtype = explode('|', str_replace('___', '/', $dbtype));
        $filterall = ($filterall == 'true');
        $cleanUp = ($cleanup == 'true');
        
        $choices = array();
        
        if (!empty($dbtype) && !empty($dbformat)) {
            $bankaccessor = new BankManagerAccessor();
            $choices = $bankaccessor->prepareBankListAjax($dbtype, $dbformat, $filterall, $cleanUp);
        }
        
        $response = new Response($choices);
        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $response->setPublic();
        $response->setMaxAge(86400);
        $response->setSharedMaxAge(86400);
        $response->headers->addCacheControlDirective('stale_while_revalidate', 300); // send cached version while revalidating (5 min delay in case biomaj server is slow)
        $response->headers->addCacheControlDirective('stale_if_error', 86400); // send cached version if error (1 day, in case biomaj server is slow)
        
        return $response;
    }
}
