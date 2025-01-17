<?php

declare(strict_types=1);

namespace Sng\Recordsmanager\Controller;

/*
 * This file is part of the "recordsmanager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Sng\Recordsmanager\Utility\Config;
use Sng\Recordsmanager\Utility\Flexfill;
use Sng\Recordsmanager\Utility\Misc;
use Sng\Recordsmanager\Utility\Query;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EditController extends AbstractController
{
    protected $currentConfig;

    /**
     * @param int $currentPage
     */
    public function indexAction(int $currentPage = 1)
    {
        $allConfigs = Config::getAllConfigs(0);
        $this->createMenu('index', $allConfigs);

        if (empty($allConfigs)) {
            return null;
        }

        $this->currentConfig = $allConfigs[0];
        $this->setCurrentConfig();

        $query = $this->buildQuery();
        $this->buildPagination($query, $currentPage);

        $this->view->assign('headers', $query->getHeaders());
        $this->view->assign('currentconfig', $this->currentConfig);
        $this->view->assign('arguments', $this->request->getArguments());
        $this->view->assign('menuitems', $allConfigs);
        $this->view->assign('returnurl', rawurlencode($this->getReturnUrl()));
        $this->view->assign('deleteurl', $this->getDeleteUrl());
        $this->view->assign('baseediturl', $this->getBaseEditUrl());

        $disableFields = '';
        if ($this->currentConfig['sqlfieldsinsert'] !== '') {
            $disableFields = implode(',', Flexfill::getDiffFieldsFromTable($this->currentConfig['sqltable'], $this->currentConfig['sqlfieldsinsert']));
        }
        $this->view->assign('disableFields', $disableFields);

        $this->moduleTemplate->setContent($this->view->render());

        return $this->htmlResponseCompatibility($this->moduleTemplate->renderContent());
    }

    /**
     * Build the query array
     *
     * @return \Sng\Recordsmanager\Utility\Query
     */
    public function buildQuery()
    {
        $arguments = $this->request->getArguments();

        $queryObject = GeneralUtility::makeInstance(Query::class);
        $queryObject->setConfig($this->currentConfig);
        $queryObject->setCheckPids(false);
        $queryObject->buildQuery();

        if (!empty($arguments['orderby'])) {
            $queryObject->setOrderBy(rawurldecode($arguments['orderby']));
        }

        return $queryObject;
    }

    /**
     * Get return url
     *
     * @return string
     */
    public function getReturnUrl()
    {
        $arguments = $this->request->getArguments();

        return $this->uriBuilder->reset()->setAddQueryString(true)->uriFor();
    }

    /**
     * Get url to delete a record
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        $arguments = $this->request->getArguments();
        $returnUrl = $this->getReturnUrl();
        $deleteUrl = Misc::getModuleUrl('tce_db');

        return $deleteUrl . ('&cmd["+table+"]["+id+"][delete]=1&redirect=' . rawurlencode($returnUrl) . '&prErr=1&uPT=1');
    }

    /**
     * Get url to edit a record
     *
     * @return string
     */
    public function getBaseEditUrl()
    {
        return Misc::getModuleUrl('record_edit') . '&';
    }

    /**
     * Set the current config record
     */
    public function setCurrentConfig()
    {
        $arguments = $this->request->getArguments();
        if (!empty($arguments['menuitem'])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_recordsmanager_config');
            $queryBuilder
                ->select('*')
                ->from('tx_recordsmanager_config')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($arguments['menuitem'], \PDO::PARAM_INT))
                );
            $this->currentConfig = $queryBuilder->execute()->fetch();
        }
    }
}
