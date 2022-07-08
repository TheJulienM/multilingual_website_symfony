<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ArticleTranslation;
use App\Entity\Language;
use App\Form\ArticleTranslationType;
use App\Form\ArticleType;
use App\Form\LanguageType;
use Doctrine\Persistence\ManagerRegistry;
use Knp\DoctrineBehaviors\Contract\Entity\TranslatableInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class MainController extends AbstractController
{

    public function checkEnglish(ManagerRegistry $doctrine) : bool {
        $manager = $doctrine->getManager();
        $english = $manager->getRepository(Language::class)->findOneBy(["lang" => "en"]);
        if(!$english) {
            return false;
        } else {
            return true;
        }

    }

    /**
     * @Route("/import-base-languages", name="import_base_languages")
     */
    public function importBaseLanguages(ManagerRegistry $doctrine, Request $request) : Response {

        if($this->checkEnglish($doctrine)) {
            return $this->redirect($request->headers->get('referer'));
        }

        $langs = ["en","fr","de","es"];
        $flags = ["en.png","fr.png","de.png","es.png"];

        for($i = 0; $i < 4; $i++) {
            $language = new Language();
            $language->setLang($langs[$i]);
            $language->setFlag($flags[$i]);
            $manager = $doctrine->getManager();
            $manager->persist($language);
            $manager->flush();
        }

        return $this->redirectToRoute('create_article');

    }

    /**
     * @Route("/create-language", name="create_language")
     */
    public function createLanguage(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger) : Response {

        if(!$this->checkEnglish($doctrine)) {
           return $this->redirectToRoute('import_base_languages');
       }

        $language = new Language();
        $formLanguage = $this->createForm(LanguageType::class, $language);

        $formLanguage->handleRequest($request);

        if($formLanguage->isSubmitted() && $formLanguage->isValid()) {

            /** @var UploadedFile $flag */
            $flag = $formLanguage->get('flag')->getData();

            if($flag) {
                $originalFilename = pathinfo($flag->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$flag->guessExtension();
                try {
                    $flag->move(
                        $this->getParameter('flags_directory'),
                        $newFilename
                    );
                }
                catch (FileException $exception) {
                    $this->addFlash('notice', $exception->getMessage());
                }

                $language->setFlag($newFilename);
            }


            $manager = $doctrine->getManager();

            $manager->persist($language);
            $manager->flush();

            return $this->redirectToRoute('read_article');

        }

        return $this->render('main/form_language.html.twig', [
            'formLanguage' => $formLanguage->createView()
        ]);


    }

    /**
     * @Route("/change-language/{language}", name="change_language")
     */
    public function changeLanguage($language, Request $request, ManagerRegistry $doctrine) : Response
    {

        if(!$this->checkEnglish($doctrine)) {
           return $this->redirectToRoute('import_base_languages');
       }

        // On stocke la langue dans la session
        $request->getSession()->set('_locale', $language);

        // On revient sur la page précédente
        return $this->redirect($request->headers->get('referer'));
    }


    /**
     * @Route("/create-article", name="create_article")
     */
    public function createArticle(ManagerRegistry $doctrine, Request $request): Response
    {

        if(!$this->checkEnglish($doctrine)) {
           return $this->redirectToRoute('import_base_languages');
       }

        /** @var TranslatableInterface $article */
        $article = new Article();
        $formArticle = $this->createForm(ArticleType::class, $article);

        $formArticle->handleRequest($request);

        if($formArticle->isSubmitted() && $formArticle->isValid()) {

            $article->translate('en')->setTitle(
                $formArticle->get('title')->getData()
            );
            $article->translate('en')->setContent(
                $formArticle->get('content')->getData()
            );
            $manager = $doctrine->getManager();
            $manager->persist($article);
            $article->mergeNewTranslations();

            $manager->flush();

            return $this->redirectToRoute('read_article');

        }

        return $this->render('main/form_article.html.twig', [
            'formArticle' => $formArticle->createView()
        ]);
    }

    /**
     * @Route("/translate-article/{lang}/{articleId}", name="translate_article")
     */
    public function translateArticle(string $lang, string $articleId, ManagerRegistry $doctrine, Request $request) {

        if(!$this->checkEnglish($doctrine)) {
           return $this->redirectToRoute('import_base_languages');
       }

        $manager = $doctrine->getManager();

        $language = $manager->getRepository(Language::class)->findOneBy(["lang" => $lang]);
        $article = $manager->getRepository(Article::class)->find(intval($articleId));

        if(!$language) {
            return $this->redirectToRoute('create_language');
        }

        elseif (!$article) {
            return $this->redirectToRoute('create_article');
        }

        else {

            $languages = $manager->getRepository(Language::class)->findAll();
            $articles = $manager->getRepository(Article::class)->findAll();

            $articleTranslation = $manager->getRepository(ArticleTranslation::class)->findOneBy(["translatable" => $article, "locale" => $lang]);
            if(!$articleTranslation) {
                $articleTranslation = new ArticleTranslation();
            }

            $formArticleTranslation = $this->createForm(ArticleTranslationType::class, $articleTranslation);

            $formArticleTranslation->get('author')->setData($article->getAuthor());
            $formArticleTranslation->get('dateCreation')->setData($article->getDateCreation());

            $formArticleTranslation->handleRequest($request);

            if ($formArticleTranslation->isSubmitted() && $formArticleTranslation->isValid()) {

                $author = $formArticleTranslation->get('author')->getData();
                $dateCreation = $formArticleTranslation->get('dateCreation')->getData();

                $article->setAuthor($author);
                $article->setDateCreation($dateCreation);

                $manager->persist($article);

                $articleTranslation->setTranslatable($article);
                $articleTranslation->setLocale($language->getLang());

                $manager->persist($articleTranslation);
                $manager->flush();


                return $this->redirectToRoute('read_article');


            }

            return $this->render('main/form_translation.html.twig', [
                'formArticleTranslation' => $formArticleTranslation->createView(),
                'languages' => $languages,
                'articles' => $articles,
            ]);

        }

    }

    /**
     * @Route("/read-articles", name="read_article")
     */
    public function readArticle(ManagerRegistry $doctrine, Request $request) : Response {

       if(!$this->checkEnglish($doctrine)) {
           return $this->redirectToRoute('import_base_languages');
       }

        $manager = $doctrine->getManager();
        $articles = $manager->getRepository(Article::class)->findAll();
        if(!$articles) {
            return $this->redirectToRoute('create_article');
        }
        $actualLanguage = $manager->getRepository(Language::class)->findOneBy(["lang" => $request->getLocale()]);

        return $this->render('main/read_article.html.twig', [
            "articles" => $articles,
            "actualLanguage" => $actualLanguage
        ]);


    }
}
