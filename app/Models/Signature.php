<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

require ('api_signature/XMLSecurityKey.php');
require ('api_signature/XMLSecurityDSig.php');
require ('api_signature/XMLSecEnc.php');
use XMLSecEnc;
class Signature extends Model
{
    use HasFactory;
    public static function signature_xml($flg_firma, $ruta,  $certOrPfx, $keyOrPass) {
        $doc = new \DOMDocument();

        $doc->formatOutput = FALSE;
        $doc->preserveWhiteSpace = TRUE;
        $doc->load($ruta);

        $objDSig = new \XMLSecurityDSig(FALSE);
        $objDSig->setCanonicalMethod(\XMLSecurityDSig::C14N);
        $options = [
            'force_uri'  => true,
            'id_name'    => 'ID',
            'overwrite'  => false,
        ];

        $objDSig->addReference($doc, \XMLSecurityDSig::SHA1, array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'), $options);
        $objKey = new \XMLSecurityKey(\XMLSecurityKey::RSA_SHA1, array('type' => 'private'));

        // Modo PEM directo (desde BD)
        $certPem = $certOrPfx;
        $privateKeyPem = $keyOrPass;

        // Cargar clave y certificado PEM
        $objKey->loadKey($privateKeyPem, false);            // false => el parámetro es contenido, no ruta
        $objDSig->add509Cert($certPem, true, false);        // true => PEM, false => no es URL

        // 4) Firmar dentro del ExtensionContent indicado
        $extNode = $doc->getElementsByTagName('ExtensionContent')->item($flg_firma);
        if (!$extNode) {
            return ['respuesta' => 'error', 'mensaje' => 'No se encontró ExtensionContent['.$flg_firma.']'];
        }
        $objDSig->sign($objKey, $extNode);

        // Atributo Id en <Signature>
        $sigNode = $doc->getElementsByTagName('Signature')->item(0);
        if ($sigNode) {
            $sigNode->setAttribute('Id', 'SignatureSP');
        }

        //===================rescatamos Codigo(HASH_CPE)==================
        $hash_cpe  = ($doc->getElementsByTagName('DigestValue')->item(0)->nodeValue) ?? null;
        $firma_cpe = ($doc->getElementsByTagName('SignatureValue')->item(0)->nodeValue) ?? null;

        $doc->save($ruta);

        return [
            'respuesta' => 'ok',
            'hash_cpe'  => $hash_cpe,
            'firma_cpe' => $firma_cpe,
        ];
    }

}
