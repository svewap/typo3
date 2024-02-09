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

namespace TYPO3\CMS\Core\Routing\Aspect;

use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Site\SiteLanguageAwareTrait;
use TYPO3\CMS\Core\Utility\MathUtility;

trait SiteLanguageAccessorTrait
{
    use SiteLanguageAwareTrait;

    /**
     * @var LanguageAspect
     */
    protected $languageAspect;

    /**
     * Resolves one record out of given language fallbacks.
     */
    protected function resolveLanguageFallback(array $results, ?string $languageFieldName, ?array $languageCodes): ?array
    {
        if ($results === []) {
            return null;
        }
        if ($languageFieldName === null || $languageCodes === null) {
            return $results[0];
        }
        usort(
            $results,
            // orders records by there occurrence in $languageFallbackIds
            static function (array $a, array $b) use ($languageFieldName, $languageCodes): int {
                $languageA = (int)$a[$languageFieldName];
                $languageB = (int)$b[$languageFieldName];
                return array_search($languageA, $languageCodes, true)
                    - array_search($languageB, $languageCodes, true);
            }
        );
        return $results[0];
    }

    /**
     * Resolves all language ids that are relevant to retrieve the most specific variant of a record.
     * The order of these ids defines the processing order concerning language fallback - most specific
     * language comes first in this array.
     *
     * + "all language (-1)", most specific if present since there cannot be any localizations
     * + "current language" most specific for the current given request context
     * + "language fallbacks" falling back to language alternatives (might include "default language")
     *
     * @return int[]
     */
    protected function resolveAllRelevantLanguageCodes()
    {
        $languageCodes = [-1, $this->siteLanguage->getLanguageCode()];
        foreach ($this->getLanguageAspect()->getFallbackChain() as $item) {
            if (in_array($item, $languageCodes, true) || !MathUtility::canBeInterpretedAsInteger($item)) {
                continue;
            }
            $languageCodes[] = (int)$item;
        }
        return $languageCodes;
    }

    /**
     * Provides LanguageAspect which contains the logic how fallbacks
     * for a given context/overlay-mode shall be handled.
     *
     * @see LanguageAspectFactory::createFromSiteLanguage
     */
    protected function getLanguageAspect(): LanguageAspect
    {
        if ($this->languageAspect === null) {
            $this->languageAspect = LanguageAspectFactory::createFromSiteLanguage($this->siteLanguage);
        }
        return $this->languageAspect;
    }
}
