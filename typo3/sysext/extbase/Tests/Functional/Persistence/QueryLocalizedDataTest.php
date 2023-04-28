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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

final class QueryLocalizedDataTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    protected PostRepository $postRepository;
    protected PersistenceManager $persistenceManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Persistence/Fixtures/translatedBlogExampleData.csv');

        $configuration = [
            'persistence' => [
                'storagePid' => 20,
            ],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ];
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);
        $this->postRepository = $this->get(PostRepository::class);
        $this->persistenceManager = $this->get(PersistenceManager::class);

        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    /**
     * Test in default language
     *
     * With overlays enabled it doesn't make a difference whether you call findByUid with translated record uid or
     * default language record uid.
     *
     * Note that with feature flag disabled, you'll get same result (not translated record) for both calls ->findByUid(2)
     * and ->findByUid(11)
     *
     * @test
     */
    public function findByUidOverlayModeOnDefaultLanguage(): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON));

        $post2 = $this->postRepository->findByUid(2);

        self::assertEquals(['Post 2', 2, 2, 'Blog 1', 1, 1, 'John', 1, 1], [
            $post2->getTitle(),
            $post2->getUid(),
            $post2->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2->getBlog()->getTitle(),
            $post2->getBlog()->getUid(),
            $post2->getBlog()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2->getAuthor()->getFirstname(),
            $post2->getAuthor()->getUid(),
            $post2->getAuthor()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
        ]);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();

        // with feature flag disable, you'll get default language object here too (Post 2).
        $post2translated = $this->postRepository->findByUid(11);
        self::assertEquals(['Post 2 - DK', 2, 11, 'Blog 1 DK', 1, 2, 'Translated John', 1, 2], [
            $post2translated->getTitle(),
            $post2translated->getUid(),
            $post2translated->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2translated->getBlog()->getTitle(),
            $post2translated->getBlog()->getUid(),
            $post2translated->getBlog()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2translated->getAuthor()->getFirstname(),
            $post2translated->getAuthor()->getUid(),
            $post2translated->getAuthor()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
        ]);
    }

    /**
     * Test in default language, overlays disabled
     *
     * @test
     */
    public function findByUidNoOverlaysDefaultLanguage(): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_OFF));

        $post2 = $this->postRepository->findByUid(2);
        self::assertEquals(['Post 2', 2, 2, 'Blog 1', 1, 1, 'John', 1, 1], [
            $post2->getTitle(),
            $post2->getUid(),
            $post2->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2->getBlog()->getTitle(),
            $post2->getBlog()->getUid(),
            $post2->getBlog()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2->getAuthor()->getFirstname(),
            $post2->getAuthor()->getUid(),
            $post2->getAuthor()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
        ]);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();

        $post2translated = $this->postRepository->findByUid(11);
        self::assertEquals(['Post 2 - DK', 2, 11, 'Blog 1 DK', 1, 2, 'Translated John', 1, 2], [
            $post2translated->getTitle(),
            $post2translated->getUid(),
            $post2translated->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2translated->getBlog()->getTitle(),
            $post2translated->getBlog()->getUid(),
            $post2translated->getBlog()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2translated->getAuthor()->getFirstname(),
            $post2translated->getAuthor()->getUid(),
            $post2translated->getAuthor()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
        ]);
    }

    /**
     * Test in language uid:1, overlays enabled
     *
     * @test
     */
    public function findByUidOverlayModeOnLanguage(): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));

        $post2 = $this->postRepository->findByUid(2);
        self::assertEquals(['Post 2 - DK', 2, 11, 'Blog 1 DK', 1, 2, 'Translated John', 1, 2], [
            $post2->getTitle(),
            $post2->getUid(),
            $post2->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2->getBlog()->getTitle(),
            $post2->getBlog()->getUid(),
            $post2->getBlog()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2->getAuthor()->getFirstname(),
            $post2->getAuthor()->getUid(),
            $post2->getAuthor()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
        ]);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();
        $post2translated = $this->postRepository->findByUid(11);
        self::assertEquals(['Post 2 - DK', 2, 11, 'Blog 1 DK', 1, 2, 'Translated John', 1, 2], [
            $post2translated->getTitle(),
            $post2translated->getUid(),
            $post2translated->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2translated->getBlog()->getTitle(),
            $post2translated->getBlog()->getUid(),
            $post2translated->getBlog()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2translated->getAuthor()->getFirstname(),
            $post2translated->getAuthor()->getUid(),
            $post2translated->getAuthor()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
        ]);
    }

    /**
     * Test in language uid:1, overlays disabled
     *
     * @test
     */
    public function findByUidNoOverlaysLanguage(): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_OFF));

        $post2 = $this->postRepository->findByUid(2);
        self::assertEquals(['Post 2 - DK', 2, 11, 'Blog 1 DK', 1, 2, 'Translated John', 1, 2], [
            $post2->getTitle(),
            $post2->getUid(),
            $post2->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2->getBlog()->getTitle(),
            $post2->getBlog()->getUid(),
            $post2->getBlog()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2->getAuthor()->getFirstname(),
            $post2->getAuthor()->getUid(),
            $post2->getAuthor()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
        ]);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();

        $post2translated = $this->postRepository->findByUid(11);
        self::assertEquals(['Post 2 - DK', 2, 11, 'Blog 1 DK', 1, 2, 'Translated John', 1, 2], [
            $post2translated->getTitle(),
            $post2translated->getUid(),
            $post2translated->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2translated->getBlog()->getTitle(),
            $post2translated->getBlog()->getUid(),
            $post2translated->getBlog()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2translated->getAuthor()->getFirstname(),
            $post2translated->getAuthor()->getUid(),
            $post2translated->getAuthor()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
        ]);
    }

    /**
     * This tests shows what query by uid returns depending on the language,
     * and used uid (default language record or translated record uid).
     * All with overlay mode enabled.
     *
     * The post with uid 2 is translated to language 1, and there has uid 11.
     *
     * @test
     */
    public function customFindByUidOverlayEnabled(): void
    {
        // we're in default lang and fetching by uid of the record in default language
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageAspect(new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON));
        $query->matching($query->equals('uid', 2));
        $post2 = $query->execute()->getFirst();

        //the expected state is the same with and without feature flag
        self::assertEquals(['Post 2', 2, 2, 'Blog 1', 1, 1, 'John', 1, 1], [
            $post2->getTitle(),
            $post2->getUid(),
            $post2->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2->getBlog()->getTitle(),
            $post2->getBlog()->getUid(),
            $post2->getBlog()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2->getAuthor()->getFirstname(),
            $post2->getAuthor()->getUid(),
            $post2->getAuthor()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
        ]);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();

        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageAspect(new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON));
        $query->matching($query->equals('uid', 11));
        $post2 = $query->execute()->getFirst();

        //this assertion is true for both enabled and disabled flag
        self::assertNull($post2);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();

        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageAspect(new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $query->matching($query->equals('uid', 2));
        $post2 = $query->execute()->getFirst();

        self::assertNull($post2);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();

        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageAspect(new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $query->matching($query->equals('uid', 11));
        $post2 = $query->execute()->getFirst();

        self::assertEquals(['Post 2 - DK', 2, 11, 'Blog 1 DK', 1, 2, 'Translated John', 1, 2], [
            $post2->getTitle(),
            $post2->getUid(),
            $post2->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2->getBlog()->getTitle(),
            $post2->getBlog()->getUid(),
            $post2->getBlog()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2->getAuthor()->getFirstname(),
            $post2->getAuthor()->getUid(),
            $post2->getAuthor()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
        ]);
    }

    /**
     * This tests shows what query by uid returns depending on the language,
     * and used uid (default language record or translated record uid).
     * All with overlay mode disabled.
     *
     * The post with uid 2 is translated to language 1, and there has uid 11.
     *
     * @test
     */
    public function customFindByUidOverlayDisabled(): void
    {
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageAspect(new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_OFF));
        $query->matching($query->equals('uid', 2));
        $post2 = $query->execute()->getFirst();

        self::assertEquals(['Post 2', 2, 2, 'Blog 1', 1, 1, 'John', 1, 1], [
            $post2->getTitle(),
            $post2->getUid(),
            $post2->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2->getBlog()->getTitle(),
            $post2->getBlog()->getUid(),
            $post2->getBlog()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2->getAuthor()->getFirstname(),
            $post2->getAuthor()->getUid(),
            $post2->getAuthor()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
        ]);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();

        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageAspect(new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_OFF));
        $query->matching($query->equals('uid', 11));
        $post2 = $query->execute()->getFirst();

        //this assertion is true for both enabled and disabled flag
        self::assertNull($post2);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();

        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageAspect(new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_OFF));
        $query->matching($query->equals('uid', 2));
        $post2 = $query->execute()->getFirst();

        self::assertNull($post2);

        //this is needed because of https://forge.typo3.org/issues/59992
        $this->persistenceManager->clearState();

        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageAspect(new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_OFF));
        $query->matching($query->equals('uid', 11));
        $post2 = $query->execute()->getFirst();

        self::assertEquals(['Post 2 - DK', 2, 11, 'Blog 1 DK', 1, 2, 'Translated John', 1, 2], [
            $post2->getTitle(),
            $post2->getUid(),
            $post2->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2->getBlog()->getTitle(),
            $post2->getBlog()->getUid(),
            $post2->getBlog()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
            $post2->getAuthor()->getFirstname(),
            $post2->getAuthor()->getUid(),
            $post2->getAuthor()->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID),
        ]);
    }

    public static function queryFirst5PostsDataProvider(): array
    {
        //put it to variable to make cases with the same expected values explicit
        $lang0Expected = [
            [
                'title' => 'Post 4',
                AbstractDomainObject::PROPERTY_UID => 4,
                AbstractDomainObject::PROPERTY_LOCALIZED_UID => 4,
                'content' => 'A - content',
                'blog.title' => 'Blog 1',
                'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'author.firstname' => 'John',
                'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'secondAuthor.firstname' => 'John',
                'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'tags' => [],
            ],
            [
                'title' => 'Post 2',
                AbstractDomainObject::PROPERTY_UID => 2,
                AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                'content' => 'B - content',
                'blog.title' => 'Blog 1',
                'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'author.firstname' => 'John',
                'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'secondAuthor.firstname' => 'John',
                'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'tags.0.name' => 'Tag2',
                'tags.0.' . AbstractDomainObject::PROPERTY_UID => 2,
                'tags.0.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                'tags.1.name' => 'Tag3',
                'tags.1.' . AbstractDomainObject::PROPERTY_UID => 3,
                'tags.1.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 3,
                'tags.2.name' => 'Tag4',
                'tags.2.' . AbstractDomainObject::PROPERTY_UID => 4,
                'tags.2.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 4,
            ],
            [
                'title' => 'Post 7',
                AbstractDomainObject::PROPERTY_UID => 7,
                AbstractDomainObject::PROPERTY_LOCALIZED_UID => 7,
                'content' => 'C - content',
                'blog.title' => 'Blog 1',
                'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'author.firstname' => 'John',
                'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'secondAuthor.firstname' => 'John',
                'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'tags' => [],
            ],
            [
                'title' => 'Post 6',
                AbstractDomainObject::PROPERTY_UID => 6,
                AbstractDomainObject::PROPERTY_LOCALIZED_UID => 6,
                'content' => 'F - content',
                'blog.title' => 'Blog 1',
                'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'author.firstname' => 'John',
                'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'secondAuthor.firstname' => 'John',
                'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'tags' => [],
            ],
            [
                'title' => 'Post 1 - not translated',
                AbstractDomainObject::PROPERTY_UID => 1,
                AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'content' => 'G - content',
                'blog.title' => 'Blog 1',
                'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'author.firstname' => 'John',
                'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'secondAuthor.firstname' => 'Never translate me henry',
                'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 3,
                'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 3,
                'tags.0.name' => 'Tag1',
                'tags.0.' . AbstractDomainObject::PROPERTY_UID => 1,
                'tags.0.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'tags.1.name' => 'Tag2',
                'tags.1.' . AbstractDomainObject::PROPERTY_UID => 2,
                'tags.1.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                'tags.2.name' => 'Tag3',
                'tags.2.' . AbstractDomainObject::PROPERTY_UID => 3,
                'tags.2.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 3,
            ],
        ];
        return [
            [
                'language' => 0,
                'overlay' => LanguageAspect::OVERLAYS_MIXED,
                'expected' => $lang0Expected,
            ],
            [
                'language' => 0,
                'overlay' => LanguageAspect::OVERLAYS_OFF,
                'expected' => $lang0Expected,
            ],
            [
                'language' => 1,
                'overlay' => LanguageAspect::OVERLAYS_MIXED,
                'expected' => [
                    [
                        'title' => 'Post 5 - DK',
                        AbstractDomainObject::PROPERTY_UID => 5,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 13,
                        'content' => 'A - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'tags' => [],
                    ],
                    [
                        'title' => 'Post 2 - DK',
                        AbstractDomainObject::PROPERTY_UID => 2,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 11,
                        'content' => 'C - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'tags.0.name' => 'Tag 3 DK',
                        'tags.0.' . AbstractDomainObject::PROPERTY_UID => 3,
                        'tags.0.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 18,
                        'tags.1.name' => 'Tag4',
                        'tags.1.' . AbstractDomainObject::PROPERTY_UID => 4,
                        'tags.1.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 4,
                        'tags.2.name' => 'Tag5',
                        'tags.2.' . AbstractDomainObject::PROPERTY_UID => 5,
                        'tags.2.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 5,
                        'tags.3.name' => 'Tag 6 DK',
                        'tags.3.' . AbstractDomainObject::PROPERTY_UID => 6,
                        'tags.3.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 19,
                        'tags.4.name' => 'Tag7',
                        'tags.4.' . AbstractDomainObject::PROPERTY_UID => 7,
                        'tags.4.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 7,
                    ],
                    [
                        'title' => 'Post 6',
                        AbstractDomainObject::PROPERTY_UID => 6,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 6,
                        'content' => 'F - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'tags' => [],
                    ],
                    [
                        'title' => 'Post 1 - not translated',
                        AbstractDomainObject::PROPERTY_UID => 1,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                        'content' => 'G - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Never translate me henry',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 3,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 3,
                        'tags.0.name' => 'Tag 1 DK',
                        'tags.0.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'tags.0.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 16,
                        'tags.1.name' => 'Tag 2 DK',
                        'tags.1.' . AbstractDomainObject::PROPERTY_UID => 2,
                        'tags.1.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 17,
                        'tags.2.name' => 'Tag 3 DK',
                        'tags.2.' . AbstractDomainObject::PROPERTY_UID => 3,
                        'tags.2.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 18,

                    ],
                    [
                        'title' => 'Post 3',
                        AbstractDomainObject::PROPERTY_UID => 3,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 3,
                        'content' => 'I - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'tags' => [],
                    ],
                ],
            ],
            'only fetch records with l10n_parent and try overlays' => [
                'language' => 1,
                'overlay' => LanguageAspect::OVERLAYS_ON,
                // here we have only 4 items instead of 5 as post "Post DK only" uid:15 has no language 0 parent,
                // so with overlay enabled it's not shown
                'expected' => [
                    [
                        'title' => 'Post 5 - DK',
                        AbstractDomainObject::PROPERTY_UID => 5,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 13,
                        'content' => 'A - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'tags' => [],
                    ],
                    [
                        'title' => 'Post 2 - DK',
                        AbstractDomainObject::PROPERTY_UID => 2,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 11,
                        'content' => 'C - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'tags.0.name' => 'Tag 3 DK',
                        'tags.0.' . AbstractDomainObject::PROPERTY_UID => 3,
                        'tags.0.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 18,
                        'tags.1.name' => 'Tag4',
                        'tags.1.' . AbstractDomainObject::PROPERTY_UID => 4,
                        'tags.1.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 4,
                        'tags.2.name' => 'Tag5',
                        'tags.2.' . AbstractDomainObject::PROPERTY_UID => 5,
                        'tags.2.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 5,
                        'tags.3.name' => 'Tag 6 DK',
                        'tags.3.' . AbstractDomainObject::PROPERTY_UID => 6,
                        'tags.3.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 19,
                        'tags.4.name' => 'Tag7',
                        'tags.4.' . AbstractDomainObject::PROPERTY_UID => 7,
                        'tags.4.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 7,
                    ],
                    [
                        'title' => 'Post 7 - DK',
                        AbstractDomainObject::PROPERTY_UID => 7,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 14,
                        'content' => 'S - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'tags' => [],
                    ],
                    [
                        'title' => 'Post 4 - DK',
                        AbstractDomainObject::PROPERTY_UID => 4,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 12,
                        'content' => 'U - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'tags' => [],
                    ],
                ],
            ],
            [
                'language' => 1,
                'overlay' => LanguageAspect::OVERLAYS_OFF,
                'expected' => [
                    [
                        'title' => 'Post 5 - DK',
                        AbstractDomainObject::PROPERTY_UID => 5,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 13,
                        'content' => 'A - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'tags' => [],
                    ],
                    [
                        'title' => 'Post DK only',
                        AbstractDomainObject::PROPERTY_UID => 15,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 15,
                        'content' => 'B - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'tags' => [],
                    ],
                    [
                        'title' => 'Post 2 - DK',
                        AbstractDomainObject::PROPERTY_UID => 2,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 11,
                        'content' => 'C - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'tags.0.name' => 'Tag 3 DK',
                        'tags.0.' . AbstractDomainObject::PROPERTY_UID => 3,
                        'tags.0.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 18,
                        'tags.1.name' => 'Tag4',
                        'tags.1.' . AbstractDomainObject::PROPERTY_UID => 4,
                        'tags.1.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 4,
                        'tags.2.name' => 'Tag5',
                        'tags.2.' . AbstractDomainObject::PROPERTY_UID => 5,
                        'tags.2.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 5,
                        'tags.3.name' => 'Tag 6 DK',
                        'tags.3.' . AbstractDomainObject::PROPERTY_UID => 6,
                        'tags.3.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 19,
                        'tags.4.name' => 'Tag7',
                        'tags.4.' . AbstractDomainObject::PROPERTY_UID => 7,
                        'tags.4.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 7,
                    ],
                    [
                        'title' => 'Post 7 - DK',
                        AbstractDomainObject::PROPERTY_UID => 7,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 14,
                        'content' => 'S - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'tags' => [],
                    ],
                    [
                        'title' => 'Post 4 - DK',
                        AbstractDomainObject::PROPERTY_UID => 4,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 12,
                        'content' => 'U - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'tags' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * This test check posts returned by repository, when changing language and languageOverlayMode
     * It also sets limit, offset to validate there are no "gaps" in pagination
     * and sorting (on a posts property)
     *
     * @test
     * @dataProvider queryFirst5PostsDataProvider
     */
    public function queryFirst5Posts(int $languageUid, string $overlay, array $expected): void
    {
        $languageAspect = new LanguageAspect($languageUid, $languageUid, $overlay);
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageAspect($languageAspect);

        $query->setOrderings([
            'content' => QueryInterface::ORDER_ASCENDING,
            'uid' => QueryInterface::ORDER_ASCENDING,
        ]);
        $query->setLimit(5);
        $query->setOffset(0);
        $posts = $query->execute()->toArray();

        self::assertCount(count($expected), $posts);
        $this->assertObjectsProperties($posts, $expected);
    }

    public static function queryPostsByPropertyDataProvider(): array
    {
        $lang0Expected = [
            [
                'title' => 'Post 5',
                AbstractDomainObject::PROPERTY_UID => 5,
                AbstractDomainObject::PROPERTY_LOCALIZED_UID => 5,
                'content' => 'Z - content',
                'blog.title' => 'Blog 1',
                'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'author.firstname' => 'John',
                'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'secondAuthor.firstname' => 'John',
                'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
            ],
            [
                'title' => 'Post 6',
                AbstractDomainObject::PROPERTY_UID => 6,
                AbstractDomainObject::PROPERTY_LOCALIZED_UID => 6,
                'content' => 'F - content',
                'blog.title' => 'Blog 1',
                'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'author.firstname' => 'John',
                'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'secondAuthor.firstname' => 'John',
                'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
                'tags' => [],
            ],
        ];

        return [
            [
                'language' => 0,
                'overlay' => LanguageAspect::OVERLAYS_MIXED,
                'expected' => $lang0Expected,
            ],
            [
                'language' => 0,
                'overlay' => LanguageAspect::OVERLAYS_ON_WITH_FLOATING,
                'expected' => $lang0Expected,
            ],
            [
                'language' => 0,
                'overlay' => LanguageAspect::OVERLAYS_OFF,
                'expected' => $lang0Expected,
            ],
            [
                'language' => 1,
                'overlay' => LanguageAspect::OVERLAYS_MIXED,
                'expected' => [
                    [
                        'title' => 'Post 6',
                        AbstractDomainObject::PROPERTY_UID => 6,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 6,
                        'content' => 'F - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                    ],
                    [
                        'title' => 'Post 5 - DK',
                        AbstractDomainObject::PROPERTY_UID => 5,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 13,
                        'content' => 'A - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                    ],
                ],
            ],
            [
                'language' => 1,
                'overlay' => LanguageAspect::OVERLAYS_ON_WITH_FLOATING,
                'expected' => [
                    [
                        'title' => 'Post 5 - DK',
                        AbstractDomainObject::PROPERTY_UID => 5,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 13,
                        'content' => 'A - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                    ],
                ],
            ],
            [
                'language' => 1,
                'overlay' => LanguageAspect::OVERLAYS_OFF,
                'expected' => [
                    [
                        'title' => 'Post 5 - DK',
                        AbstractDomainObject::PROPERTY_UID => 5,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 13,
                        'content' => 'A - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                    ],
                    [
                        'title' => 'Post DK only',
                        AbstractDomainObject::PROPERTY_UID => 15,
                        AbstractDomainObject::PROPERTY_LOCALIZED_UID => 15,
                        'content' => 'B - content',
                        'blog.title' => 'Blog 1 DK',
                        'blog.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'blog.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'author.firstname' => 'Translated John',
                        'author.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'author.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                        'secondAuthor.firstname' => 'Translated John',
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_UID => 1,
                        'secondAuthor.' . AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                    ],
                ],
            ],
        ];
    }

    /**
     * This test check posts returned by repository, when filtering by property
     *
     * "Post 6" is not translated
     * "Post 5" is translated as "Post 5 - DK"
     * "Post DK only" has no translation parent
     *
     * @test
     * @dataProvider queryPostsByPropertyDataProvider
     */
    public function queryPostsByProperty(int $languageUid, string $overlay, array $expected): void
    {
        $languageAspect = new LanguageAspect($languageUid, $languageUid, $overlay);
        $query = $this->postRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setLanguageAspect($languageAspect);

        $query->matching(
            $query->logicalOr(
                $query->like('title', 'Post 5%'),
                $query->like('title', 'Post 6%'),
                $query->like('title', 'Post DK only')
            )
        );
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = $query->execute()->toArray();

        self::assertCount(count($expected), $posts);
        $this->assertObjectsProperties($posts, $expected);
    }

    public static function postsWithoutRespectingSysLanguageDataProvider(): array
    {
        $allLanguages = [
             [
                 'title' => 'Blog 1',
                 AbstractDomainObject::PROPERTY_UID => 1,
                 AbstractDomainObject::PROPERTY_LOCALIZED_UID => 1,
             ],
             [
                 'title' => 'Blog 1 DK',
                 AbstractDomainObject::PROPERTY_UID => 1,
                 AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
             ],
        ];
        return [
            'default with overlays' => [
                 'language' => 0,
                 'overlay' => LanguageAspect::OVERLAYS_ON,
                 'expected' => $allLanguages,
             ],
             'default without overlays, show all languages' => [
                 'language' => 0,
                 'overlay' => LanguageAspect::OVERLAYS_OFF,
                 'expected' => $allLanguages,
             ],
             'DA with overlays, shows translated records twice (which is a bug)' => [
                 'language' => 1,
                 'overlay' => LanguageAspect::OVERLAYS_ON,
                 'expected' => [
                     [
                         'title' => 'Blog 1 DK',
                         AbstractDomainObject::PROPERTY_UID => 1,
                         AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                     ],
                     [
                         'title' => 'Blog 1 DK',
                         AbstractDomainObject::PROPERTY_UID => 1,
                         AbstractDomainObject::PROPERTY_LOCALIZED_UID => 2,
                     ],
                 ],
             ],
            'DA without overlays, queries DA language directly' => [
                 'language' => 1,
                 'overlay' => LanguageAspect::OVERLAYS_OFF,
                 'expected' => $allLanguages,
             ],
         ];
    }

    /**
     * This test demonstrates how query behaves when setRespectSysLanguage is set to false.
     * The test now documents the WRONG behaviour described in https://forge.typo3.org/issues/45873.
     *
     * The expected state is that when setRespectSysLanguage is false, then both: default language record,
     * and translated language record should be returned. Regardless of the language setting or the overlay mode.
     * Now we're getting same record twice in some cases.
     *
     * @test
     * @dataProvider postsWithoutRespectingSysLanguageDataProvider
     */
    public function postsWithoutRespectingSysLanguage(int $languageUid, string $overlay, array $expected): void
    {
        $languageAspect = new LanguageAspect($languageUid, $languageUid, $overlay);
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', $languageAspect);

        $blogRepository = $this->get(BlogRepository::class);
        $query = $blogRepository->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setRespectSysLanguage(false);
        $querySettings->setLanguageAspect($languageAspect);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);

        $posts = $query->execute()->toArray();

        self::assertCount(count($expected), $posts);
        $this->assertObjectsProperties($posts, $expected);
    }

    /**
     * Compares array of domain objects with array containing properties values
     *
     * @param array $objects
     * @param array $expected array of expected property values [ ['property' => 'value'], ['property' => 'value2']]
     */
    protected function assertObjectsProperties($objects, $expected): void
    {
        $actual = [];
        foreach ($objects as $key => $post) {
            $actualPost = [];
            $propertiesToCheck = array_keys($expected[$key]);
            foreach ($propertiesToCheck as $propertyPath) {
                $actualPost[$propertyPath] = self::getPropertyPath($post, $propertyPath);
            }
            $actual[] = $actualPost;
            self::assertEquals($expected[$key], $actual[$key], 'Assertion of the $expected[' . $key . '] failed');
        }
        self::assertEquals($expected, $actual);
    }

    /**
     * This is a copy of the ObjectAccess::getPropertyPath, but with the fallback
     * to access protected properties, and iterator_to_array added.
     *
     * @param mixed $subject Object or array to get the property path from
     * @param string $propertyPath
     *
     * @return mixed Value of the property
     */
    protected static function getPropertyPath($subject, $propertyPath)
    {
        $propertyPathSegments = explode('.', $propertyPath);
        try {
            foreach ($propertyPathSegments as $pathSegment) {
                $subject = ObjectAccess::getPropertyInternal($subject, $pathSegment);
                if ($subject instanceof \SplObjectStorage || $subject instanceof ObjectStorage) {
                    $subject = iterator_to_array(clone $subject, false);
                }
            }
        } catch (PropertyNotAccessibleException $error) {
            // Workaround for this test
            $propertyReflection = new \ReflectionProperty($subject, $pathSegment);
            return $propertyReflection->getValue($subject);
        }
        return $subject;
    }
}
