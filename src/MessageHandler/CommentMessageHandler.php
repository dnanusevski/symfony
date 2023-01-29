<?php

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;


#[AsMessageHandler]
class CommentMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SpamChecker $spamChecker,
        private CommentRepository $commentRepository,
        private MessageBusInterface $bus,
        // it knows which flow to use because we have named our argument commentStateMachine (comment)
        private WorkflowInterface $commentStateMachine,
        private MailerInterface $mailer,
        #[Autowire('%admin_email%')] private string $adminEmail,
        private ?LoggerInterface $logger = null,
    ) {
    }
    public function __invoke(CommentMessage $message)
    {
        $comment = $this->commentRepository->find($message->getId());

        if (!$comment) {
            return;
        }

        if ($this->commentStateMachine->can($comment, 'accept')) {
            $score = $this->spamChecker->getSpamScore($comment, $message->getContext());
            $transition = match ($score) {
                2 => 'reject_spam',
                1 => 'might_be_spam',
                default => 'accept',
            };
            $this->commentStateMachine->apply($comment, $transition);
            $this->entityManager->flush();
            $this->logger->debug('Sent email comment: '.$comment);
            $this->bus->dispatch($message);
        } elseif (
            $this->commentStateMachine->can($comment, 'publish') ||
            $this->commentStateMachine->can($comment, 'publish_ham')
        ) {
            /*
            // isntead of this we are going to send email to admin so taht he ca take care of publishing
            $this->commentStateMachine->apply(
                $comment,
                $this->commentStateMachine->can($comment, 'publish') ? 'publish' : 'publish_ham'
            );

            $this->entityManager->flush();
            */
            $this->mailer->send((new NotificationEmail())
                ->subject('New comment posted')
                ->htmlTemplate('emails/comment_notification.html.twig')
                ->from($this->adminEmail)
                ->to($this->adminEmail)
                ->context(['comment' => $comment])
            );
               
        } elseif ($this->logger) {
            $this->logger->debug('Dropping comment message', [
                'comment' => $comment->getId(),
                'state' => $comment->getState()
            ]);
        }

        /*
        // this is commented due to that we are going to use workflow that is noted in workflow.yaml
        if (2 === $this->spamChecker->getSpamScore(
            $comment,
            $message->getContext()
        )) {
            $comment->setState('spam');
        } else {
            $comment->setState('published');
        }

        $this->entityManager->flush();
        */
    }
}
