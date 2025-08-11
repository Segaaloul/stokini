<?php

namespace App\MessageHandler;

use App\Message\OptimizeImageMessage;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Imagine\Image\ImageInterface;
use App\Entity\Dossier;
use Doctrine\ORM\EntityManagerInterface;

class OptimizeImageHandler implements MessageHandlerInterface
{
    private string $uploadsDir;
    private string $uploadsOptimizedDir;
    private EntityManagerInterface $em;

    public function __construct(string $uploadsDir, string $uploadsOptimizedDir, EntityManagerInterface $em)
    {
        // dump($uploadsDir, $uploadsOptimizedDir);
        // die('Constructor called');

        $this->uploadsDir = $uploadsDir;
        $this->uploadsOptimizedDir = $uploadsOptimizedDir;
        $this->em = $em;
    }

    public function __invoke(OptimizeImageMessage $message)
    {
        // Récupérer le dossier via Doctrine pour avoir CreatedBy et Nom
        $dossier = $this->em->getRepository(Dossier::class)->find($message->getDossierId());

        if (!$dossier) {
            // Gestion d’erreur (ex : return, throw)
            return;
        }

        $originalPath = $this->uploadsDir . '/' . $dossier->getCreatedBy() . '/' . $dossier->getNom() . '/' . $message->getFilename();
        $optimizedDir = $this->uploadsOptimizedDir . '/' . $dossier->getCreatedBy() . '/' . $dossier->getNom();


        $optimizedPath = $optimizedDir . '/' . $message->getFilename();

        $imagine = new Imagine();
        $image = $imagine->open($originalPath);

        // Lire les données EXIF
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($originalPath);
            if ($exif && isset($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3:
                        $image->rotate(180);
                        break;
                    case 6:
                        $image->rotate(90);
                        break;
                    case 8:
                        $image->rotate(-90);
                        break;
                }
            }
        }

        // Redimensionner si trop large
        $size = $image->getSize();
        if ($size->getWidth() > 1920) {
            $image->resize(new Box(1920, intval(1920 * $size->getHeight() / $size->getWidth())));
        }

        $image->save($optimizedPath, ['jpeg_quality' => 85]);
    }
}
