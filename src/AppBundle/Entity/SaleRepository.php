<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

class SaleRepository extends EntityRepository
{
    public function removeSales()
    {
        // Truncating to reset IDs
        $connection = $this->getEntityManager()->getConnection();
        $connection->exec('TRUNCATE TABLE sale');
    }

    public function findTopCustomers()
    {
        $result = $this->createQueryBuilder('s')
            ->select(['s.licenseId', 's.organisationName', 'SUM(s.vendorAmount) as total'])
            ->groupBy('s.licenseId')
            ->orderBy('total', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findSalesForChart()
    {
        $sales = $this->findAll();

        $groupedSales = [];
        foreach ($sales as $sale) {
            $this->addMonltySale($groupedSales, $sale);
        }

        $groupedSales = array_reverse($groupedSales, true);
        $groupedSales = array_slice($groupedSales, -6, 6, true);

        return $groupedSales;
    }

    private function addMonltySale(&$groupedSales, Sale $sale)
    {
        if (!isset($groupedSales[$sale->getDate()->format('Y-m')])) {
            $monthlySale = [
                'new' => 0.00,
                'renewal' => 0.00,
                'other' => 0.00
            ];
        } else {
            $monthlySale = $groupedSales[$sale->getDate()->format('Y-m')];
        }

        switch ($sale->getSaleType()) {
            case 'Renewal':
                $monthlySale['renewal'] += $sale->getVendorAmount();
                break;
            case 'New':
                $monthlySale['new'] += $sale->getVendorAmount();
                break;
            default:
                $monthlySale['other'] += $sale->getVendorAmount();
                break;
        }

        $groupedSales[$sale->getDate()->format('Y-m')] = $monthlySale;
    }
}