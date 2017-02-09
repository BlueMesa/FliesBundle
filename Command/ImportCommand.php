<?php

/*
 * Copyright 2011 Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Bluemesa\Bundle\FliesBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

use Doctrine\Common\Collections\ArrayCollection;

use Bluemesa\Bundle\FliesBundle\Entity\Stock;
use Bluemesa\Bundle\FliesBundle\Entity\StockVial;

/**
 * ImportCommand
 *
 * @author Radoslaw Kamil Ejsmont <radoslaw@ejsmont.net>
 */
class ImportCommand extends Command
{
    private $container;

    protected function configure()
    {
        $this
            ->setName('labdb:flies:import')
            ->setDescription('Import stocks from spreadsheet')
            ->addArgument(
                'excel',
                InputArgument::REQUIRED,
                'Spreadsheet file to import from'
            )
            ->addArgument(
                'logfile',
                InputArgument::REQUIRED,
                'Log file'
            )
            ->addArgument(
                'listfile',
                InputArgument::OPTIONAL,
                'List of stocks to import'
            )
            ->addOption(
               'print',
               null,
               InputOption::VALUE_NONE,
               'Print labels for new vials'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $excelFile = $input->getArgument('excel');

        $logfilename = $input->getArgument('logfile');
        $logfile = fopen($logfilename,'w');

        $listfilename = $input->getArgument('listfile');
        $importlist = array();
        if ($listfilename) {
            $listfile = fopen($listfilename,'r');
            if ($listfile) {
                while ($data = fgetcsv($listfile,0,"\t")) {
                    $importlist[] = $data[0];
                }
            }
        }

        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelperSet()->get('question');
        $this->container = $this->getApplication()->getKernel()->getContainer();

        /** @var \PHPExcel $excel */
        $excel = $this->container->get('phpexcel')->createPHPExcelObject($excelFile);

        $dm = $this->container->get('doctrine')->getManager();
        $om = $this->container->get('bluemesa.core.doctrine.registry')->getManagerForClass('Bluemesa\Bundle\FliesBundle\Entity\Stock');
        $vm = $this->container->get('bluemesa.core.doctrine.registry')->getManagerForClass('Bluemesa\Bundle\FliesBundle\Entity\StockVial');

        $om->disableAutoAcl();
        $vm->disableAutoAcl();

        $sheet = $excel->setActiveSheetIndex(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $stocks = array();
        $stock_register = array();
        $vials = array();

        for ($row = 2; $row <= $highestRow; $row++) {
            $data = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, false);
            $owner_name = trim($data[0][0]);
            $stock_name = trim($data[0][1]);
            $stock_genotype = trim($data[0][2]);
            $creator_name = trim($data[0][4]);
            $stock_notes = trim($data[0][5]);
            $stock_vendor = trim($data[0][6]);
            $stock_vendor_id = trim($data[0][7]);
            $stock_info_url = str_replace(" ","",trim($data[0][8]));
            $stock_verified = trim($data[0][9]) == "yes" ? true : false;
            $stock_vials_size = trim($data[0][10]);
            $stock_vials_size = $stock_vials_size == "" ? 'medium' : $stock_vials_size;
            $stock_vials_number = (integer) trim($data[0][11]);
            $stock_vials_number = $stock_vials_number <= 0 ? 1 : $stock_vials_number;
            $stock_vials_food = trim($data[0][12]);
            $stock_vials_food = $stock_vials_food == "" ? 'standard' : $stock_vials_food;

            $test = $om->getRepository('Bluemesa\Bundle\FliesBundle\Entity\Stock')->findOneByName($stock_name);

            if ((!in_array($stock_name, $stock_register))&&(null === $test)) {

                if (($stock_vendor != "")&&($stock_vendor_id != "")) {
                    $output->write("Querying FlyBase for " . $stock_name . ": ");
                    $stock_data = $this->getStockData($stock_vendor, $stock_vendor_id);
                    if (count($stock_data) == 1) {
                        $stock_genotype = $stock_data[0]['stock_genotype'];
                        $stock_info_url = $stock_data[0]['stock_link'];
                        $output->writeln("success");
                    } elseif (count($stock_data) != 1) {
                        $output->writeln("failed");
                    }
                }

                $stock = new Stock();
                $stock->setName($stock_name);
                $stock->setGenotype($stock_genotype);
                $stock->setNotes($stock_notes);
                $stock->setVendor($stock_vendor);
                $stock->setVendorId($stock_vendor_id);
                $stock->setInfoURL($stock_info_url);
                $stock->setVerified($stock_verified);

                for ($i = 0; $i < $stock_vials_number - 1; $i++) {
                    $vial = new StockVial();
                    $stock->addVial($vial);
                }
                $stock_vials = $stock->getVials();
                foreach ($stock_vials as $vial) {
                    $vial->setSize($stock_vials_size);
                    $vial->setFood($stock_vials_food);
                }

                $stock_register[] = $stock_name;
                $stocks[$owner_name][$stock_name] = $stock;
            } else {
                $vials[$owner_name][$stock_name]['size'] = $stock_vials_size;
                $vials[$owner_name][$stock_name]['number'] = $stock_vials_number;
                $vials[$owner_name][$stock_name]['food'] = $stock_vials_food;
            }
        }

        $dm->getConnection()->beginTransaction();
        
        foreach ($stocks as $user_name => $user_stocks) {
            
            try {
                $user = $this->container->get('user_provider')->loadUserByUsername($user_name);
            } catch (UsernameNotFoundException $e) {
                $user = null;
            }
            
            if ($user instanceof UserInterface) {
                $output->writeln("Adding stocks for user " . $user_name . ":");
                $userStocks = new ArrayCollection();
                $userVials = new ArrayCollection();
                foreach ($user_stocks as $stock_name => $stock) {
                    $om->persist($stock);
                    $userStocks->add($stock);
                    $userVials->add($stock->getVials());
                    $output->write(".");
                    fprintf($logfile,"%s\n",$stock->getName());
                }
                $om->flush();
                $output->writeln("");
                $output->write("Creating ACLs...");
                $om->createACL($userStocks, $user);
                $vm->createACL($userVials, $user);
                $output->writeln(" done");
            } else {
                $output->writeln("<error>User " . $user_name . " does not exits. Skipping!</error>");
            }
        }
        
        foreach ($vials as $user_name => $user_vials) {
            try {
                $user = $this->container->get('user_provider')->loadUserByUsername($user_name);
            } catch (UsernameNotFoundException $e) {
                $user = null;
            }
            
            if ($user instanceof UserInterface) {
                $output->writeln("Adding vials for user " . $user_name . ":");
                $userVials = new ArrayCollection();
                foreach ($user_vials as $stock_name => $stock_vials) {
                    $stock = $om->getRepository('Bluemesa\Bundle\FliesBundle\Entity\Stock')->findOneByName($stock_name);
                    if ($stock instanceof Stock) {
                        $stockVials = new ArrayCollection();
                        for ($i = 0; $i < $stock_vials['number']; $i++) {
                            $vial = new StockVial();
                            $stock->addVial($vial);
                            $stockVials->add($vial);
                            $userVials->add($vial);
                        }
                        foreach ($stockVials as $vial) {
                            $vial->setSize($stock_vials['number']);
                            $vial->setFood($stock_vials['number']);
                            $vm->persist($vial);
                        }
                        $output->write(".");
                    } else {
                        $output->write("?");
                    }
                }
                $output->writeln("");
                $vm->flush();
                $vm->createACL($userVials, $user);
            } else {
                $output->writeln("<error>User " . $user_name . " does not exits. Skipping!</error>");
            }
        }
        
        $message = 'Stocks and vials have been created. Commit?';
        $question = new ConfirmationQuestion(sprintf('<question>' . $message . '</question>', false));
        if ($questionHelper->ask($input, $output, $question)) {
            $dm->getConnection()->commit();
            $output->writeln("<info>Import successful!</info>");
        } else {
            $dm->getConnection()->rollback();
            $dm->getConnection()->close();
            $output->writeln("<comment>Import cancelled!</comment>");
        }
        
        $om->enableAutoAcl();
        $vm->enableAutoAcl();
    }
    
    protected function getStockData($vendor, $stock)
    {
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
    WHERE stock.name = :stock AND stockcollection.uniquename = :vendor
FLYBASE_SQL;
        $conn = $this->container->get('doctrine.dbal.flybase_connection');
        $stmt = $conn->prepare($sql);
        $stmt->bindValue("stock", $stock);
        if ($vendor !== '') {
            $stmt->bindValue("vendor", $vendor);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
