<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\SpamChecker;


class ConferenceControllerTest extends WebTestCase
{
    /*
    public function testIndex()
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $response = $client->getResponse();
        //echo $response; to see the response
        $this->assertResponseIsSuccessful();
        //$this->assertSelectorTextContains('h2', 'Give your feedback');
    }

    public function testConferencePage()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertCount(2, $crawler->filter('h4')); // there should be 2 h4 tags

        $client->clickLink('View'); // clikc on the first one you find
        //$client->click($crawler->filter('h4 + p a')->link()); instead of clicking on View you can also do this

        $this->assertPageTitleContains('Amsterdam');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Amsterdam 2019');
        $this->assertSelectorExists('div:contains("There are 1 comments")');
        // run the test with this command
        //symfony php bin/phpunit tests/Controller/ConferenceControllerTest.php
    }

    */

    public function testCommentSubmission()
    {
        // this test will fail if SpamChecker is not mocked to prophesi
        //$mock = $this->prophesize(SpamChecker::class);
        //$mock->getSpamScore('Some feedback from an automated functional test')->shouldBeCalled()->willReturn('true');;


        $client = static::createClient();
        $client->request('GET', '/conference/amsterdam-2019');
        $client->submitForm('Submit', [
            'comment_form[author]' => 'Fabien',
            'comment_form[text]' => 'Some feedback from an automated functional test',
            //'comment_form[email]' => 'me@automat.ed',
            'comment_form[email]' => $email = 'me@automat.ed',
            'comment_form[photo]' => dirname(__DIR__, 2) . '/public/images/under.png',

        ]);
        $this->assertResponseRedirects();

        // simulate comment validation
        $comment = self::getContainer()->get(CommentRepository::class)->findOneByEmail($email);
        $comment->setState('published');
        self::getContainer()->get(EntityManagerInterface::class)->flush();


        $client->followRedirect();
        $this->assertSelectorExists('div:contains("There are 2 comments")');
    }
}
