<?php

namespace App\Controller;

use App\Entity\Conference;
use App\Form\CommentFormType;
use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use Doctrine\ORM\EntityManagerInterface;

class ConferenceController extends AbstractController
{


    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/', name: 'homepage')]
    //public function index(Request $request): Response
    //public function index(Environment $twig, ConferenceRepository $conferenceRepository): Response

    //we will omit twig and relay on this->render
    public function index(ConferenceRepository $conferenceRepository): Response
    {

        //dump($request);
        //exit();
        /*
        return $this->render('conference/index.html.twig', [
            'controller_name' => 'ConferenceController',
        ]);
        */
        /*
        $greet = "";
        if ($name = $request->query->get('hello')) {
            $greet = sprintf('<h1>Hello %s</h1>', htmlspecialchars($name));
        }

        return new Response(
            <<<EOF
            <html>
                <body>
                    $greet
                    <img src="/images/under.png" />
                </body>
            </html>
            EOF
        );
        */


        /*
        // yes we can omit twig eviorment
        return new Response($twig->render('conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ]));
        */

        return $this->render('conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ]);
    }



    //public function show(Environment $twig, Conference $conference, CommentRepository $commentRepository): Response


    //we will omit twig and relay on this->render
    //public function show(Request $request, Environment $twig, Conference $conference, CommentRepository $commentRepository): Response
    //#[Route('/conference/{id}', name: 'conference')]

    // we need to add photos so the following line is commented
    //public function show(Request $request, Conference $conference, CommentRepository $commentRepository): Response
    // after adding photos we have this
    #[Route('/conference/{slug}', name: 'conference')]
    public function show(
        Request $request,
        Conference $conference,
        CommentRepository $commentRepository,
        #[Autowire('%photo_dir%')] string $photoDir,
    ): Response {




        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setConference($conference);

            if ($photo = $form['photo']->getData()) {
                $filename = bin2hex(random_bytes(6)) . '.' . $photo->guessExtension();
                try {
                    $photo->move($photoDir, $filename);
                } catch (FileException $e) {
                    // unable to upload the photo, give up
                }
                $comment->setPhotoFilename($filename);
            }

            $this->entityManager->persist($comment);
            $this->entityManager->flush();
            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }

        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $commentRepository->getCommentPaginator($conference, $offset);

        /*
        return new Response($twig->render('conference/show.html.twig', [
            'conference' => $conference,
            //'comments' => $commentRepository->findBy(['conference' => $conference], ['createdAt' => 'DESC']),
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
        ]));
        */

        return $this->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
            'comment_form' => $form,
        ]);
    }

    #[Route('/hello/{name}', name: 'hello')]
    public function hello(string $name = ''): Response
    {

        $greet = '';
        if ($name) {
            $greet = sprintf('<h1>Hello %s!</h1>', htmlspecialchars($name));
        }

        return new Response(
            <<<EOF
            <html>
                <body>
                    $greet
                </body>
            </html>
            EOF
        );
    }
}
