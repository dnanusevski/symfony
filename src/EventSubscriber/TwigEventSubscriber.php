<?php

namespace App\EventSubscriber;

use App\Repository\ConferenceRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;

class TwigEventSubscriber implements EventSubscriberInterface
{

    private $twig;
    private $conferenceRepository;
    
    public function __construct(Environment $twig, ConferenceRepository $conferenceRepository)
    {
         //we do not need this anymore since we are using ESIs for printing conferences
        $this->twig = $twig;
        $this->conferenceRepository = $conferenceRepository;
    }

    public function onControllerEvent(ControllerEvent $event): void
    {
        // we do not need this anymore since we are using ESIs for printing conferences
        // ...
        $this->twig->addGlobal('conferences', $this->conferenceRepository->findAll());
    }

    public static function getSubscribedEvents(): array
    {
        // we do not need this anymore since we are using ESIs for printing conferences
        
        return [
            ControllerEvent::class => 'onControllerEvent',
        ];
        
    }
}
