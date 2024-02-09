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

namespace TYPO3\CMS\Core\Site\Entity;

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Entity representing a site for everything on "pid=0". Mostly used in TYPO3 Backend, not really in use elsewhere.
 */
class NullSite implements SiteInterface
{
    protected int $rootPageId = 0;

    /**
     * @var SiteLanguage[]
     */
    protected array $languages;

    /**
     * Sets up a null site object
     *
     * @param array|null $languages site languages
     * @param Uri|null $baseEntryPoint
     */
    public function __construct(array $languages = null, Uri $baseEntryPoint = null)
    {
        if (empty($languages)) {
            // Create the default language if no language configuration is given
            $this->languages[0] = new SiteLanguage(
                'en-US',
                '',
                new Uri('/'),
                ['enabled' => true]
            );
        } else {
            foreach ($languages as $languageConfiguration) {
                $languageCode = (string)$languageConfiguration['languageCode'];
                // Language configuration does not have a base defined
                // So the main site base is used (usually done for default languages)
                $this->languages[$languageCode] = new SiteLanguage(
                    $languageCode,
                    $languageConfiguration['locale'] ?? '',
                    $baseEntryPoint ?: new Uri('/'),
                    $languageConfiguration
                );
            }
        }
    }

    /**
     * Returns always #NULL
     */
    public function getIdentifier(): string
    {
        return '#NULL';
    }

    /**
     * Always "/"
     */
    public function getBase(): UriInterface
    {
        return new Uri('/');
    }

    /**
     * Always zero
     */
    public function getRootPageId(): int
    {
        return 0;
    }

    /**
     * Returns all available languages of this installation
     *
     * @return SiteLanguage[]
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    /**
     * Returns a language of this site, given by the language_tag
     *
     * @throws \InvalidArgumentException
     */
    public function getLanguageByCode(string $languageCode): SiteLanguage
    {
        if (isset($this->languages[$languageCode])) {
            return $this->languages[$languageCode];
        }
        throw new \InvalidArgumentException(
            'Language ' . $languageCode . ' does not exist on site ' . $this->getIdentifier() . '.',
            1522965188
        );
    }

    public function getDefaultLanguage(): SiteLanguage
    {
        return reset($this->languages);
    }

    /**
     * This takes page TSconfig into account (unlike Site interface) to find
     * mod.SHARED.disableLanguages and mod.SHARED.defaultLanguageLabel
     */
    public function getAvailableLanguages(BackendUserAuthentication $user, bool $includeAllLanguagesFlag = false, int $pageId = null): array
    {
        $availableLanguages = [];

        // Check if we need to add language "-1"
        if ($includeAllLanguagesFlag && $user->checkLanguageAccess(-1)) {
            $availableLanguages[-1] = new SiteLanguage('all', '', $this->getBase(), [
                'title' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:multipleLanguages'),
                'flag' => 'flags-multiple',
            ]);
        }
        $pageTs = BackendUtility::getPagesTSconfig((int)$pageId);
        $pageTs = $pageTs['mod.']['SHARED.'] ?? [];

        $disabledLanguages = GeneralUtility::intExplode(',', (string)($pageTs['disableLanguages'] ?? ''), true);
        // Do not add the ones that are not allowed by the user
        foreach ($this->languages as $language) {
            if ($user->checkLanguageAccess($language->getLanguageCode()) && !in_array($language->getLanguageCode(), $disabledLanguages, true)) {
                if ($language->getLanguageCode() === 0) {
                    // 0: "Default" language
                    $defaultLanguageLabel = 'LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:defaultLanguage';
                    $defaultLanguageLabel = $this->getLanguageService()->sL($defaultLanguageLabel);
                    if (isset($pageTs['defaultLanguageLabel'])) {
                        $defaultLanguageLabel = $pageTs['defaultLanguageLabel'] . ' (' . $defaultLanguageLabel . ')';
                    }
                    $defaultLanguageFlag = '';
                    if (isset($pageTs['defaultLanguageFlag'])) {
                        $defaultLanguageFlag = 'flags-' . $pageTs['defaultLanguageFlag'];
                    }
                    $language = new SiteLanguage('en-US', '', $language->getBase(), [
                        'title' => $defaultLanguageLabel,
                        'flag' => $defaultLanguageFlag,
                    ]);
                }
                $availableLanguages[$language->getLanguageCode()] = $language;
            }
        }

        return $availableLanguages;
    }

    /**
     * Returns a ready-to-use error handler, to be used within the ErrorController
     */
    public function getErrorHandler(int $statusCode): PageErrorHandlerInterface
    {
        throw new \RuntimeException('No error handler given for the status code "' . $statusCode . '".', 1522495102);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
