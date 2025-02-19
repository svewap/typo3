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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Link;

use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ActionViewHelperTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $configurationToUseInTestInstance = [
        'FE' => [
            'cacheHash' => [
                'excludedParameters' => [
                    'untrusted',
                ],
            ],
        ],
    ];

    /**
     * @test
     */
    public function renderThrowsExceptionWithoutARequest(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1690365240);
        $view = new StandaloneView();
        $view->setRequest();
        $view->setTemplateSource('<f:link.action />');
        $view->render();
    }

    /**
     * @test
     */
    public function renderInFrontendCoreContextThrowsExceptionWithIncompleteArguments(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = $request->withAttribute('routing', new PageArguments(1, '0', []));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1690370264);
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:link.action />');
        $view->render();
    }

    /**
     * @test
     */
    public function renderInBackendCoreContextThrowsExceptionWithIncompleteArguments(): void
    {
        $request = new ServerRequest('http://localhost/typo3/');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withQueryParams(['route' => 'web_layout']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1690365240);
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource('<f:link.action />');
        $view->render();
    }

    public static function renderInFrontendWithCoreContextAndAllNecessaryExtbaseArgumentsDataProvider(): \Generator
    {
        yield 'link to root page with plugin' => [
            '<f:link.action pageUid="1" extensionName="examples" pluginName="haiku" controller="Detail" action="show">link to root page with plugin</f:link.action>',
            '<a href="/?tx_examples_haiku%5Baction%5D=show&amp;tx_examples_haiku%5Bcontroller%5D=Detail&amp;cHash=5c6aa07f6ceee30ae2ea8dbf574cf26c">link to root page with plugin</a>',
        ];

        yield 'link to root page with plugin and section' => [
            '<f:link.action pageUid="1" extensionName="examples" pluginName="haiku" controller="Detail" action="show" section="c13">link to root page with plugin and section</f:link.action>',
            '<a href="/?tx_examples_haiku%5Baction%5D=show&amp;tx_examples_haiku%5Bcontroller%5D=Detail&amp;cHash=5c6aa07f6ceee30ae2ea8dbf574cf26c#c13">link to root page with plugin and section</a>',
        ];

        yield 'link to root page with page type' => [
            '<f:link.action pageUid="1" extensionName="examples" pluginName="haiku" controller="Detail" action="show" pageType="1234">link to root page with page type</f:link.action>',
            '<a href="/?tx_examples_haiku%5Baction%5D=show&amp;tx_examples_haiku%5Bcontroller%5D=Detail&amp;type=1234&amp;cHash=5c6aa07f6ceee30ae2ea8dbf574cf26c">link to root page with page type</a>',
        ];
    }

    /**
     * @test
     * @dataProvider renderInFrontendWithCoreContextAndAllNecessaryExtbaseArgumentsDataProvider
     */
    public function renderInFrontendWithCoreContextAndAllNecessaryExtbaseArguments(string $template, string $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
        );
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = $request->withAttribute('routing', new PageArguments(1, '0', ['untrusted' => 123]));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $GLOBALS['TSFE'] = $this->createMock(TypoScriptFrontendController::class);
        $GLOBALS['TSFE']->id = 1;
        $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource($template);
        $result = $view->render();
        self::assertSame($expected, $result);
    }

    public static function renderInFrontendWithExtbaseContextDataProvider(): \Generator
    {
        // with all extbase arguments provided
        yield 'link to root page with plugin' => [
            '<f:link.action pageUid="1" extensionName="examples" pluginName="haiku" controller="Detail" action="show">link to root page with plugin</f:link.action>',
            '<a href="/?tx_examples_haiku%5Baction%5D=show&amp;tx_examples_haiku%5Bcontroller%5D=Detail&amp;cHash=5c6aa07f6ceee30ae2ea8dbf574cf26c">link to root page with plugin</a>',
        ];

        yield 'link to root page with plugin and section' => [
            '<f:link.action pageUid="1" extensionName="examples" pluginName="haiku" controller="Detail" action="show" section="c13">link to root page with plugin and section</f:link.action>',
            '<a href="/?tx_examples_haiku%5Baction%5D=show&amp;tx_examples_haiku%5Bcontroller%5D=Detail&amp;cHash=5c6aa07f6ceee30ae2ea8dbf574cf26c#c13">link to root page with plugin and section</a>',
        ];

        yield 'link to root page with page type' => [
            '<f:link.action pageUid="1" extensionName="examples" pluginName="haiku" controller="Detail" action="show" pageType="1234">link to root page with page type</f:link.action>',
            '<a href="/?tx_examples_haiku%5Baction%5D=show&amp;tx_examples_haiku%5Bcontroller%5D=Detail&amp;type=1234&amp;cHash=5c6aa07f6ceee30ae2ea8dbf574cf26c">link to root page with page type</a>',
        ];
        // without all extbase arguments provided
        yield 'renderProvidesATagForValidLinkTarget' => [
            '<f:link.action>index.php</f:link.action>',
            '<a href="/?tx_examples_haiku%5Bcontroller%5D=Detail&amp;cHash=1d5a12de6bf2d5245b654deb866ee9c3">index.php</a>',
        ];
        yield 'renderWillProvideEmptyATagForNonValidLinkTarget' => [
            '<f:link.action></f:link.action>',
            '<a href="/?tx_examples_haiku%5Bcontroller%5D=Detail&amp;cHash=1d5a12de6bf2d5245b654deb866ee9c3"></a>',
        ];
        yield 'link to root page in extbase context' => [
            '<f:link.action pageUid="1">linkMe</f:link.action>',
            '<a href="/?tx_examples_haiku%5Bcontroller%5D=Detail&amp;cHash=1d5a12de6bf2d5245b654deb866ee9c3">linkMe</a>',
        ];
        yield 'link to root page with section' => [
            '<f:link.action pageUid="1" section="c13">linkMe</f:link.action>',
            '<a href="/?tx_examples_haiku%5Bcontroller%5D=Detail&amp;cHash=1d5a12de6bf2d5245b654deb866ee9c3#c13">linkMe</a>',
        ];
        yield 'link to root page with page type in extbase context' => [
            '<f:link.action pageUid="1" pageType="1234">linkMe</f:link.action>',
            '<a href="/?tx_examples_haiku%5Bcontroller%5D=Detail&amp;type=1234&amp;cHash=1d5a12de6bf2d5245b654deb866ee9c3">linkMe</a>',
        ];
        yield 'link to root page with untrusted query arguments' => [
            '<f:link.action addQueryString="untrusted"></f:link.action>',
            '<a href="/?tx_examples_haiku%5Bcontroller%5D=Detail&amp;untrusted=123&amp;cHash=1d5a12de6bf2d5245b654deb866ee9c3"></a>',
        ];
        yield 'link to page sub page' => [
            '<f:link.action pageUid="3">linkMe</f:link.action>',
            '<a href="/dummy-1-2/dummy-1-2-3?tx_examples_haiku%5Bcontroller%5D=Detail&amp;cHash=d9289022f99f8cbc8080832f61e46509">linkMe</a>',
        ];
        yield 'arguments one level' => [
            '<f:link.action pageUid="3" arguments="{foo: \'bar\'}">haiku title</f:link.action>',
            '<a href="/dummy-1-2/dummy-1-2-3?tx_examples_haiku%5Bcontroller%5D=Detail&amp;tx_examples_haiku%5Bfoo%5D=bar&amp;cHash=74dd4635cee85b19b67cd9b497ec99e9">haiku title</a>',
        ];
        yield 'additional parameters two levels' => [
            '<f:link.action pageUid="3" additionalParams="{tx_examples_haiku: {action: \'show\', haiku: 42}}">haiku title</f:link.action>',
            '<a href="/dummy-1-2/dummy-1-2-3?tx_examples_haiku%5Baction%5D=show&amp;tx_examples_haiku%5Bcontroller%5D=Detail&amp;tx_examples_haiku%5Bhaiku%5D=42&amp;cHash=aefc37bc2323ebd8c8e39c222adb7413">haiku title</a>',
        ];
    }

    /**
     * @test
     * @dataProvider renderInFrontendWithExtbaseContextDataProvider
     */
    public function renderInFrontendWithExtbaseContext(string $template, string $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
        );
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), []);
        $frontendTypoScript->setSetupArray([]);
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerExtensionName('Examples');
        $extbaseRequestParameters->setControllerName('Detail');
        $extbaseRequestParameters->setControllerActionName('show');
        $extbaseRequestParameters->setPluginName('Haiku');
        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $request = $request->withAttribute('routing', new PageArguments(1, '0', ['untrusted' => 123]));
        $request = $request->withAttribute('extbase', $extbaseRequestParameters);
        $request = $request->withAttribute('currentContentObject', $this->get(ContentObjectRenderer::class));
        $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);
        $request = new Request($request);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $GLOBALS['TSFE'] = $this->createMock(TypoScriptFrontendController::class);
        $GLOBALS['TSFE']->id = 1;
        $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $view = new StandaloneView();
        $view->setRequest($request);
        $view->setTemplateSource($template);
        $result = $view->render();
        self::assertSame($expected, $result);
    }
}
