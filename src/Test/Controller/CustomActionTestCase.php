<?php

declare(strict_types=1);

namespace Protung\EasyAdminPlusBundle\Test\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @template TCrudController
 * @template-extends AdminControllerWebTestCase<TCrudController>
 */
abstract class CustomActionTestCase extends AdminControllerWebTestCase
{
    protected static ?string $expectedPageTitle = null;

    abstract protected function actionName(): string;

    protected function expectedPageTitle(): ?string
    {
        return static::$expectedPageTitle;
    }

    /**
     * @param array<string,string> $queryParameters
     */
    protected function assertPageLoadsViewForAction(array $queryParameters = []): void
    {
        $queryParameters[EA::CRUD_ACTION] = $this->actionName();

        $this->assertRequestGet($queryParameters);
    }

    /**
     * @param array<string,mixed>  $requestData
     * @param array<string,string> $queryParameters
     * @param array<string,mixed>  $files
     */
    protected function assertSubmittingFormAndRedirectingToListView(array $requestData, array $queryParameters = [], array $files = []): void
    {
        $this->submitFormRequest($requestData, $queryParameters, $files);

        $expectedRedirectUrl = 'http://localhost' . $this->prepareAdminUrl([EA::CRUD_ACTION => Action::INDEX]);
        self::assertTrue($this->getClient()->getResponse()->isRedirect($expectedRedirectUrl));
    }

    /**
     * @param array<string,array<string|array>> $formExpectedErrors
     * @param array<string,mixed>               $requestData
     * @param array<string,string>              $queryParameters
     * @param array<string,mixed>               $files
     */
    protected function assertSubmittingFormAndShowingValidationErrors(array $formExpectedErrors, array $requestData, array $queryParameters = [], array $files = []): void
    {
        $crawler = $this->submitFormRequest($requestData, $queryParameters, $files);

        self::assertResponseStatusCode($this->getClient()->getResponse(), Response::HTTP_OK);

        $form     = $this->findForm($crawler);
        $formName = $form->getFormNode()->getAttribute('name');

        $fields = $form->get($formName);

        $actual = $this->mapFieldsErrors($crawler, $fields);

        $formExpectedErrors['_token'] = [];
        $this->assertMatchesPattern($formExpectedErrors, $actual);
    }

    /**
     * @param array<string,mixed>  $requestData
     * @param array<string,string> $queryParameters
     * @param array<string,mixed>  $files
     */
    protected function submitFormRequest(array $requestData, array $queryParameters = [], array $files = []): Crawler
    {
        $queryParameters[EA::CRUD_ACTION] = $this->actionName();

        $crawler       = $this->assertRequestGet($queryParameters);
        $expectedTitle = $this->expectedPageTitle();
        if ($expectedTitle !== null) {
            $this->assertPageTitle($expectedTitle);
        }

        $form     = $this->findForm($crawler);
        $formName = $form->getFormNode()->getAttribute('name');

        $requestData['_token'] = $this->getCsrfToken($formName);
        $values                = [$formName => $requestData];
        $files                 = [$formName => $files];

        return $this->getClient()->request(Request::METHOD_POST, $this->prepareAdminUrl($queryParameters), $values, $files);
    }

    private function findForm(Crawler $crawler): Form
    {
        return $crawler->filter('.content-wrapper form')->form();
    }
}
