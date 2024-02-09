<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Backend\Controller\Page;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Controller\Event\AfterPageColumnsSelectedForLocalizationEvent;
use TYPO3\CMS\Backend\Controller\Event\AfterRecordSummaryForLocalizationEvent;
use TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * LocalizationController handles the AJAX requests for record localization
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class LocalizationController
{
    /**
     * @var string
     */
    public const ACTION_COPY = 'copyFromLanguage';

    /**
     * @var string
     */
    public const ACTION_LOCALIZE = 'localize';

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var LocalizationRepository
     */
    protected $localizationRepository;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->localizationRepository = GeneralUtility::makeInstance(LocalizationRepository::class);
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
    }

    /**
     * Get used languages in a page
     */
    public function getUsedLanguagesInPage(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        if (!isset($params['pageId'], $params['languageCode'])) {
            return new JsonResponse(null, 400);
        }

        $pageId = (int)$params['pageId'];
        $languageTag = $params['languageCode'];

        $translationProvider = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
        $systemLanguages = $translationProvider->getSystemLanguages($pageId);

        $availableLanguages = [];

        // First check whether column has localized records
        $elementsInColumnCount = $this->localizationRepository->getLocalizedRecordCount($pageId, $languageTag);
        $result = [];
        if ($elementsInColumnCount !== 0) {
            // check elements in column - empty if source records do not exist anymore
            $result = $this->localizationRepository->fetchOriginLanguage($pageId, $languageTag);
            if ($result !== []) {
                $availableLanguages[] = $systemLanguages[$result['language_tag']];
            }
        }
        if ($elementsInColumnCount === 0 || $result === []) {
            $fetchedAvailableLanguages = $this->localizationRepository->fetchAvailableLanguages($pageId, $languageTag);
            foreach ($fetchedAvailableLanguages as $language) {
                if (isset($systemLanguages[$language['language_tag']])) {
                    $availableLanguages[] = $systemLanguages[$language['language_tag']];
                }
            }
        }
        // Language "All" should not appear as a source of translations (see bug 92757) and keys should be sequential
        $availableLanguages = array_values(
            array_filter($availableLanguages, static function (array $languageRecord): bool {
                return (int)$languageRecord['uid'] !== -1;
            })
        );

        // Pre-render all flag icons
        foreach ($availableLanguages as &$language) {
            if ($language['flagIcon'] === 'empty-empty') {
                $language['flagIcon'] = '';
            } else {
                $language['flagIcon'] = $this->iconFactory->getIcon($language['flagIcon'], IconSize::SMALL)->render();
            }
        }

        return new JsonResponse($availableLanguages);
    }

    /**
     * Get a prepared summary of records being translated
     */
    public function getRecordLocalizeSummary(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        if (!isset($params['pageId'], $params['destLanguageCode'], $params['languageCode'])) {
            return new JsonResponse(null, 400);
        }

        $pageId = (int)$params['pageId'];
        $destLanguageCode = (int)$params['destLanguageCode'];
        $languageTag = (int)$params['languageCode'];

        $records = [];
        $result = $this->localizationRepository->getRecordsToCopyDatabaseResult(
            $pageId,
            $destLanguageCode,
            $languageTag,
            '*'
        );

        $flatRecords = [];
        while ($row = $result->fetchAssociative()) {
            BackendUtility::workspaceOL('tt_content', $row, -99, true);
            if (!$row || VersionState::tryFrom($row['t3ver_state'] ?? 0) === VersionState::DELETE_PLACEHOLDER) {
                continue;
            }
            $colPos = $row['colPos'];
            if (!isset($records[$colPos])) {
                $records[$colPos] = [];
            }
            $records[$colPos][] = [
                'icon' => $this->iconFactory->getIconForRecord('tt_content', $row, IconSize::SMALL)->render(),
                'title' => $row[$GLOBALS['TCA']['tt_content']['ctrl']['label']],
                'uid' => $row['uid'],
            ];
            $flatRecords[] = $row;
        }

        $columns = $this->getPageColumns($pageId, $flatRecords, $params);
        $event = new AfterRecordSummaryForLocalizationEvent($records, $columns);
        $this->eventDispatcher->dispatch($event);

        return new JsonResponse([
            'records' => $event->getRecords(),
            'columns' => $event->getColumns(),
        ]);
    }

    public function localizeRecords(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        if (!isset($params['pageId'], $params['srcLanguageCode'], $params['destLanguageCode'], $params['action'], $params['uidList'])) {
            return new JsonResponse(null, 400);
        }

        if ($params['action'] !== static::ACTION_COPY && $params['action'] !== static::ACTION_LOCALIZE) {
            $response = new Response('php://temp', 400, ['Content-Type' => 'application/json; charset=utf-8']);
            $response->getBody()->write('Invalid action "' . $params['action'] . '" called.');
            return $response;
        }

        // Filter transmitted but invalid uids
        $params['uidList'] = $this->filterInvalidUids(
            (int)$params['pageId'],
            (int)$params['destLanguageCode'],
            (int)$params['srcLanguageCode'],
            $params['uidList']
        );

        $this->process($params);

        return new JsonResponse([]);
    }

    /**
     * Gets all possible UIDs of a page, colPos and language that might be processed and removes invalid UIDs that might
     * be smuggled in.
     */
    protected function filterInvalidUids(
        int $pageId,
        int $destLanguageCode,
        int $srcLanguageCode,
        array $transmittedUidList
    ): array {
        // Get all valid uids that can be processed
        $validUidList = $this->localizationRepository->getRecordsToCopyDatabaseResult(
            $pageId,
            $destLanguageCode,
            $srcLanguageCode,
            'uid'
        );

        return array_intersect(array_unique($transmittedUidList), array_column($validUidList->fetchAllAssociative(), 'uid'));
    }

    /**
     * Processes the localization actions
     *
     * @param array $params
     */
    protected function process($params): void
    {
        $destLanguageCode = (int)$params['destLanguageCode'];

        // Build command map
        $cmd = [
            'tt_content' => [],
        ];

        if (isset($params['uidList']) && is_array($params['uidList'])) {
            foreach ($params['uidList'] as $currentUid) {
                if ($params['action'] === static::ACTION_LOCALIZE) {
                    $cmd['tt_content'][$currentUid] = [
                        'localize' => $destLanguageCode,
                    ];
                } else {
                    $cmd['tt_content'][$currentUid] = [
                        'copyToLanguage' => $destLanguageCode,
                    ];
                }
            }
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();
    }

    protected function getPageColumns(int $pageId, array $flatRecords, array $params): array
    {
        $columns = [];
        $backendLayoutView = GeneralUtility::makeInstance(BackendLayoutView::class);
        $backendLayout = $backendLayoutView->getBackendLayoutForPage($pageId);

        foreach ($backendLayout->getUsedColumns() as $columnPos => $columnLabel) {
            $columns[$columnPos] = $GLOBALS['LANG']->sL($columnLabel);
        }

        $event = new AfterPageColumnsSelectedForLocalizationEvent($columns, array_values($backendLayout->getColumnPositionNumbers()), $backendLayout, $flatRecords, $params);
        $this->eventDispatcher->dispatch($event);

        return [
            'columns' => $event->getColumns(),
            'columnList' => $event->getColumnList(),
        ];
    }
}
