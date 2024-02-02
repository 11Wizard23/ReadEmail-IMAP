<?php


use App\Service\DigitalOcean\SvcDigitalOceanSpaces;
use Illuminate\Support\Facades\Log;
use Webklex\IMAP\Facades\Client;
use Webklex\PHPIMAP\Client as PhpImapClient;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;

class SvcEmailLeer
{
    private PhpImapClient $cliente;

    /**
     * @throws ImapBadRequestException
     * @throws RuntimeException
     * @throws ResponseException
     * @throws ConnectionFailedException
     * @throws ImapServerErrorException
     * @throws AuthFailedException
     */
    public function __construct()
    {
        $this->cliente = Client::account('default');
        $this->cliente->connect();
    }


    /**
     * Obtiene los mensajes en una carpeta indicada
     * Puede filtrar los correos a obtener.
     *
     * @param string $nombreCarpeta
     * @param bool $mensajesSinLeer
     * @param array $filters
     * @param bool $subirArchivosAws
     * @return array
     */
    public function obtenerMensajes(string $nombreCarpeta, bool $mensajesSinLeer = false, array $filters = [], bool $subirArchivosAws = false): array
    {
        $mensajesRetorno = [];
        try {
            $carpeta = $this->cliente->getFolder($nombreCarpeta);
            $mensajes = $carpeta->messages()->all();
            if (!empty($filters)) {
                foreach ($filters as $filter => $value) {
                    $mensajes = $mensajes->where($filter, $value);
                }
            }
            if ($mensajesSinLeer) {
                $mensajes = $mensajes->unseen();
            }
            $mensajes = $mensajes->get();

            if ($subirArchivosAws) {
                $clienteOcean = new SvcDigitalOceanSpaces();
            }

            foreach ($mensajes as $mensaje) {
                $dataMensaje = [
                    "uid"            => $mensaje->getUid(),
                    "fecha"          => $mensaje->getDate()->toString(),
                    "remitente"      => $mensaje->getFrom()->first()->toArray(),
                    "asunto"         => $mensaje->getSubject()->toString(),
                    "en_respuesta_a" => $mensaje->getInReplyTo()->toString(),
                    "mensaje"        => $mensaje->getTextBody()
                ];
                //Se recorren los adjuntos
                foreach ($mensaje->getAttachments()->getIterator() as $adjunto) {
                    $dataAdjunto = [
                        "id"        => $adjunto->getId(),
                        "nombre"    => $adjunto->getName(),
                        "content"   => $adjunto->getContent(),
                        "extension" => $adjunto->getExtension()
                    ];
                    if ($subirArchivosAws) {
                        $dataAdjunto['url'] = $clienteOcean->subirArchivo($adjunto->getId(), $adjunto->getName(), $adjunto->getContent());
                    }
                    $dataMensaje['adjuntos'][] = $dataAdjunto;
                }
                $mensajesRetorno[] = $dataMensaje;
            }
            return $mensajesRetorno;
        } catch (\Exception $e) {
            Log::channel('email')->info($e->getMessage());
        }
        return [];
    }

    public function marcarMensaje(string $nombreCarpeta, mixed $uid, string $estado): bool
    {
        try {
            $mensaje = $this->cliente->getFolder($nombreCarpeta)->messages()->getMessage($uid);
            $mensaje->addFlag($estado);
            return true;
        } catch (\Exception $e) {
            Log::channel('email')->info($e->getMessage());
            return false;
        }
    }
}
