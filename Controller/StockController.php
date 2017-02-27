<?php

/*
 * This file is part of the FliesBundle.
 *
 * Copyright (c) 2017 BlueMesa LabDB Contributors <labdb@bluemesa.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bluemesa\Bundle\FliesBundle\Controller;

use Bluemesa\Bundle\AclBundle\Controller\AclControllerTrait;
use Bluemesa\Bundle\AclBundle\Controller\Annotations as ACL;
use Bluemesa\Bundle\CoreBundle\Controller\Annotations\Paginate;
use Bluemesa\Bundle\CrudBundle\Controller\Annotations as CRUD;
use Bluemesa\Bundle\CrudBundle\Controller\CrudControllerTrait;
use FOS\RestBundle\Controller\Annotations as REST;
use FOS\RestBundle\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


/**
 * StockController class
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 *
 * @REST\Prefix("/flies/stocks")
 * @REST\NamePrefix("bluemesa_flies_stock_")
 * @CRUD\Controller()
 */
class StockController extends Controller
{
    use AclControllerTrait;
    use CrudControllerTrait;


    /**
     * @CRUD\Action("index")
     * @CRUD\Filter("Bluemesa\Bundle\FliesBundle\Filter\StockFilter",
     *     redirectRoute="bluemesa_flies_stock_index_type_sort")
     * @REST\View()
     * @REST\Get("", defaults={"_format" = "html"}))
     * @REST\Get("/{access}", name="_access",
     *     requirements={"access" = "mtnt|private|shared|public"},
     *     defaults={"_format" = "html"})
     * @REST\Get("/{access}/sort/{sort}/{order}", name="_sort",
     *     requirements={"access" = "mtnt|private|shared|public"},
     *     defaults={"sort" = "name", "order" = "asc", "_format" = "html"})
     * @Paginate(25)
     *
     * @param  Request     $request
     * @return View
     */
    public function indexAction(Request $request)
    {
        return $this->getCrudHandler()->handle($request);
    }

    /**
     * @CRUD\Action("delete")
     * @REST\View()
     * @REST\Route("/{id}/delete", methods={"GET", "DELETE", "POST"}, requirements={"id"="\d+"}, defaults={"_format" = "html"})
     * @REST\Delete("/{id}", name="_rest", requirements={"id"="\d+"}, defaults={"_format" = "html"})
     *
     * @param  Request     $request
     * @return View
     */
    public function deleteAction(Request $request)
    {
        return $this->getCrudHandler()->handle($request);
    }

    /**
     * @CRUD\Action("show")
     * @REST\View()
     * @REST\Get("/{id}", requirements={"id"="\d+"}, defaults={"_format" = "html"})
     *
     * @param  Request  $request
     * @return View
     */
    public function showAction(Request $request)
    {
        return $this->getCrudHandler()->handle($request);
    }

    /**
     * @CRUD\Action("new")
     * @REST\View()
     * @REST\Route("/new", methods={"GET", "PUT"}, defaults={"_format" = "html"})
     * @REST\Put("", name="_rest", defaults={"_format" = "html"})
     *
     * @param  Request     $request
     * @return View
     */
    public function newAction(Request $request)
    {
        return $this->getCrudHandler()->handle($request);
    }

    /**
     * @CRUD\Action("edit")
     * @REST\View()
     * @REST\Route("/{id}/edit", methods={"GET", "POST"}, requirements={"id"="\d+"}, defaults={"_format" = "html"})
     * @REST\Post("/{id}", name="_rest", requirements={"id"="\d+"}, defaults={"_format" = "html"})
     *
     * @param  Request     $request
     * @return View
     */
    public function editAction(Request $request)
    {
        return $this->getCrudHandler()->handle($request);
    }

    /**
     * @ACL\Action("permissions")
     * @REST\View()
     * @REST\Route("/{id}/edit/permissions", methods={"GET", "POST"},
     *     requirements={"id"="\d+"}, defaults={"_format" = "html"})
     *
     * @param  Request     $request
     * @return View
     */
    public function permissionsAction(Request $request)
    {
        return $this->getAclHandler()->handle($request);
    }

    /**
     * @REST\Get("/_ajax/flybase/stock", defaults={"_format" = "json"}, requirements={"_format" = "json"})
     * @REST\RequestParam(name="stock")
     * @REST\RequestParam(name="vendor")
     * @REST\View()
     *
     * @param  Request $request
     * @return View
     */
    public function ajaxFlybaseStockAction(Request $request)
    {
        $stock = $request->query->get('stock');
        $vendor = $request->query->get('vendor', '');
        $sql = <<<FLYBASE_SQL
SELECT stockcollection.uniquename AS stock_center,
    stock.name AS stock_id,
    'http://flybase.org/reports/' || stock.uniquename || '.html' AS stock_link,
    genotype.uniquename AS stock_genotype
    FROM stock
    JOIN stock_genotype on stock.stock_id = stock_genotype.stock_id
    JOIN genotype on stock_genotype.genotype_id = genotype.genotype_id
    JOIN stockcollection_stock on stock.stock_id = stockcollection_stock.stock_id
    JOIN stockcollection on stockcollection_stock.stockcollection_id = stockcollection.stockcollection_id
    WHERE stock.name ILIKE :stock
FLYBASE_SQL;
        if ($vendor !== '') {
            $sql .= ' AND stockcollection.uniquename ILIKE :vendor';
        }
        $sql .= ' ORDER BY char_length(stock.name), stock.name LIMIT 10';
        $conn = $this->get('doctrine.dbal.flybase_connection');
        $stmt = $conn->prepare($sql);
        $stmt->bindValue("stock", "%" . $stock . "%");
        if ($vendor !== '') {
            $stmt->bindValue("vendor", "%" . $vendor . "%");
        }
        $stmt->execute();
        $stocks = $stmt->fetchAll();

        return new View($stocks);
    }

    /**
     * @REST\Get("/_ajax/flybase/vendor", defaults={"_format" = "json"}, requirements={"_format" = "json"})
     * @REST\RequestParam(name="vendor")
     * @REST\View()
     *
     * @param  Request       $request
     * @return View
     */
    public function ajaxFlybaseVendorAction(Request $request)
    {
        $vendor = $request->query->get('vendor');
        $sql = <<<FLYBASE_SQL
SELECT stockcollection.uniquename AS stock_center
    FROM stockcollection
    WHERE stockcollection.uniquename ILIKE :vendor
FLYBASE_SQL;
        $conn = $this->get('doctrine.dbal.flybase_connection');
        $stmt = $conn->prepare($sql);
        $stmt->bindValue("vendor", "%" . $vendor . "%");
        $stmt->execute();
        $vendors = $stmt->fetchAll();

        return new View($vendors);
    }
}
