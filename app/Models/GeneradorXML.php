<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Luecano\NumeroALetras\NumeroALetras;

class GeneradorXML extends Model
{
    use HasFactory;
    public static function  CrearXMLFactura($nombrexml, $emisor, $cliente, $comprobante, $detalle)
    {

        $doc = new \DOMDocument();
        $doc->formatOutput = FALSE;
        $doc->preserveWhiteSpace = TRUE;
        $doc->encoding = 'utf-8';
        // LISTADO DE LAS CUOTAS - INICIO
        $cuotas = DB::table('ventas_cuotas')->where([['id_venta','=',$comprobante->id_venta],['venta_cuota_estado','=',1]])->get();
        // LISTADO DE LAS CUOTAS - FINAL

        // TOTAL EN LETRAS - INICIO
        $da = new NumeroALetras();
        $total_letras = $da->toInvoice($comprobante->venta_total,'2','soles');
        // TOTAL EN LETRAS - FINAL

        // RAZÓN SOCIAL DEL CLIENTE - INICIO
        $tipo_documento_emisor = "6";
        $razon_social = $cliente->id_tipo_documento == 4 ? $cliente->cliente_razonsocial : $cliente->cliente_nombre;
        // RAZÓN SOCIAL DEL CLIENTE - FINAL

        $anho = date('Y');
        if($anho == "2021"){
            $icbper = "0.30";
        }elseif($anho == "2022"){
            $icbper = "0.40";
        }else{
            $icbper = "0.50";
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
      <Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
         <ext:UBLExtensions>
            <ext:UBLExtension>
               <ext:ExtensionContent />
            </ext:UBLExtension>
         </ext:UBLExtensions>
         <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
         <cbc:CustomizationID>2.0</cbc:CustomizationID>
         <cbc:ID>'.$comprobante->venta_serie.'-'.$comprobante->venta_correlativo.'</cbc:ID>
         <cbc:IssueDate>'.date('Y-m-d', strtotime($comprobante->venta_fecha)).'</cbc:IssueDate>
         <cbc:IssueTime>'.date('H:i:s', strtotime($comprobante->venta_fecha)).'</cbc:IssueTime>
         <cbc:DueDate>'.date('Y-m-d', strtotime($comprobante->venta_fecha)).'</cbc:DueDate>
         <cbc:InvoiceTypeCode listID="0101">'.$comprobante->venta_tipo.'</cbc:InvoiceTypeCode>
         <cbc:Note languageLocaleID="1000"><![CDATA['.$total_letras.']]></cbc:Note>
         <cbc:DocumentCurrencyCode>'.$comprobante->abrstandar.'</cbc:DocumentCurrencyCode>
         <cac:Signature>
            <cbc:ID>'.$emisor->empresa_ruc.'</cbc:ID>
            <cbc:Note><![CDATA['.$emisor->empresa_nombrecomercial.']]></cbc:Note>
            <cac:SignatoryParty>
               <cac:PartyIdentification>
                  <cbc:ID>'.$emisor->empresa_ruc.'</cbc:ID>
               </cac:PartyIdentification>
               <cac:PartyName>
                  <cbc:Name><![CDATA['.$emisor->empresa_razon_social.']]></cbc:Name>
               </cac:PartyName>
            </cac:SignatoryParty>
            <cac:DigitalSignatureAttachment>
               <cac:ExternalReference>
                  <cbc:URI>#SIGN-EMPRESA</cbc:URI>
               </cac:ExternalReference>
            </cac:DigitalSignatureAttachment>
         </cac:Signature>
         <cac:AccountingSupplierParty>
            <cac:Party>
               <cac:PartyIdentification>
                  <cbc:ID schemeID="'.$tipo_documento_emisor.'">'.$emisor->empresa_ruc.'</cbc:ID>
               </cac:PartyIdentification>
               <cac:PartyName>
                  <cbc:Name><![CDATA['.$emisor->empresa_nombrecomercial.']]></cbc:Name>
               </cac:PartyName>
               <cac:PartyLegalEntity>
                  <cbc:RegistrationName><![CDATA['.$emisor->empresa_razon_social.']]></cbc:RegistrationName>
                  <cac:RegistrationAddress>
                     <cbc:ID>'.$emisor->ubigeo_cod.'</cbc:ID>
                     <cbc:AddressTypeCode>0000</cbc:AddressTypeCode>
                     <cbc:CitySubdivisionName>NONE</cbc:CitySubdivisionName>
                     <cbc:CityName>'.$emisor->ubigeo_provincia.'</cbc:CityName>
                     <cbc:CountrySubentity>'.$emisor->ubigeo_departamento.'</cbc:CountrySubentity>
                     <cbc:District>'.$emisor->ubigeo_distrito.'</cbc:District>
                     <cac:AddressLine>
                        <cbc:Line><![CDATA['.$emisor->empresa_domiciliofiscal.']]></cbc:Line>
                     </cac:AddressLine>
                     <cac:Country>
                        <cbc:IdentificationCode>'.$emisor->empresa_pais.'</cbc:IdentificationCode>
                     </cac:Country>
                  </cac:RegistrationAddress>
               </cac:PartyLegalEntity>
            </cac:Party>
         </cac:AccountingSupplierParty>
         <cac:AccountingCustomerParty>
            <cac:Party>
               <cac:PartyIdentification>
                  <cbc:ID schemeID="'.$cliente->tipodocumento_codigo.'">'.$cliente->cliente_numero.'</cbc:ID>
               </cac:PartyIdentification>
               <cac:PartyLegalEntity>
                  <cbc:RegistrationName><![CDATA['.$razon_social.']]></cbc:RegistrationName>
                  <cac:RegistrationAddress>
                     <cac:AddressLine>
                        <cbc:Line><![CDATA['.$cliente->cliente_direccion.']]></cbc:Line>
                     </cac:AddressLine>
                     <cac:Country>
                        <cbc:IdentificationCode>PE</cbc:IdentificationCode>
                     </cac:Country>
                  </cac:RegistrationAddress>
               </cac:PartyLegalEntity>
            </cac:Party>
         </cac:AccountingCustomerParty>';

        if($comprobante->id_formas_pago == 1){
            $xml.='<cac:PaymentTerms>
                    <cbc:ID>FormaPago</cbc:ID>
                    <cbc:PaymentMeansID>Contado</cbc:PaymentMeansID>
                   </cac:PaymentTerms>';
        }else{
            $xml.='<cac:PaymentTerms>
                        <cbc:ID>FormaPago</cbc:ID>
                        <cbc:PaymentMeansID>Credito</cbc:PaymentMeansID>
                        <cbc:Amount currencyID="PEN">'.$comprobante->venta_total.'</cbc:Amount>
                     </cac:PaymentTerms>';
            $a = 1;

            foreach ($cuotas as $cu){
                $nroCuota = str_pad((string)$a, 3, '0', STR_PAD_LEFT); // 001, 002, 003...
                $importe = number_format((float)$cu->venta_cuota_importe, 2, '.', '');
                $fecha   = date('Y-m-d', strtotime($cu->venta_cuota_fecha)); // formato estándar XML

                $xml.=
                    '<cac:PaymentTerms>
                            <cbc:ID>FormaPago</cbc:ID>
                            <cbc:PaymentMeansID>Cuota'.$nroCuota.'</cbc:PaymentMeansID>
                            <cbc:Amount currencyID="PEN">'.$importe.'</cbc:Amount>
                            <cbc:PaymentDueDate>'.$fecha.'</cbc:PaymentDueDate>
                        </cac:PaymentTerms>';
                $a++;
            }

        }
        $impuesto = $comprobante->venta_totaligv + $comprobante->venta_icbper;

        $xml.='<cac:TaxTotal>
            <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">'.$impuesto.'</cbc:TaxAmount>';
        if ($comprobante->venta_totalgravada > 0) {
            $xml .= '<cac:TaxSubtotal>
               <cbc:TaxableAmount currencyID="' . $comprobante->abrstandar . '">' . $comprobante->venta_totalgravada . '</cbc:TaxableAmount>
               <cbc:TaxAmount currencyID="' . $comprobante->abrstandar . '">' . $comprobante->venta_totaligv . '</cbc:TaxAmount>
               <cac:TaxCategory>
                  <cac:TaxScheme>
                     <cbc:ID>1000</cbc:ID>
                     <cbc:Name>IGV</cbc:Name>
                     <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                  </cac:TaxScheme>
               </cac:TaxCategory>
            </cac:TaxSubtotal>';
        }


        if($comprobante->venta_totalexonerada > 0){
            $xml.='<cac:TaxSubtotal>
                  <cbc:TaxableAmount currencyID="'.$comprobante->abrstandar.'">'.$comprobante->venta_totalexonerada.'</cbc:TaxableAmount>
                  <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">0.00</cbc:TaxAmount>
                  <cac:TaxCategory>
                     <cbc:ID schemeID="UN/ECE 5305" schemeName="Tax Category Identifier" schemeAgencyName="United Nations Economic Commission for Europe">E</cbc:ID>
                     <cac:TaxScheme>
                        <cbc:ID schemeID="UN/ECE 5153" schemeAgencyID="6">9997</cbc:ID>
                        <cbc:Name>EXO</cbc:Name>
                        <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                     </cac:TaxScheme>
                  </cac:TaxCategory>
               </cac:TaxSubtotal>';
        }

        if($comprobante->venta_totalinafecta>0){
            $xml.='<cac:TaxSubtotal>
                  <cbc:TaxableAmount currencyID="'.$comprobante->abrstandar.'">'.$comprobante->venta_totalinafecta.'</cbc:TaxableAmount>
                  <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">0.00</cbc:TaxAmount>
                  <cac:TaxCategory>
                     <cbc:ID schemeID="UN/ECE 5305" schemeName="Tax Category Identifier" schemeAgencyName="United Nations Economic Commission for Europe">E</cbc:ID>
                     <cac:TaxScheme>
                        <cbc:ID schemeID="UN/ECE 5153" schemeAgencyID="6">9998</cbc:ID>
                        <cbc:Name>INA</cbc:Name>
                        <cbc:TaxTypeCode>FRE</cbc:TaxTypeCode>
                     </cac:TaxScheme>
                  </cac:TaxCategory>
               </cac:TaxSubtotal>';
        }
        if($comprobante->venta_totalgratuita>0){
            $xml.='<cac:TaxSubtotal>
                  <cbc:TaxableAmount currencyID="'.$comprobante->abrstandar.'">'. number_format($comprobante->venta_totalgratuita, 2).'</cbc:TaxableAmount>
                  <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">0.00</cbc:TaxAmount>
                  <cac:TaxCategory>
                    <cbc:ID schemeID="UN/ECE 5305" schemeName="Tax Category Identifier" schemeAgencyName="United Nations Economic Commission for Europe">Z</cbc:ID>
                    <cac:TaxScheme>
                       <cbc:ID>9996</cbc:ID>
                       <cbc:Name>GRA</cbc:Name>
                       <cbc:TaxTypeCode>FRE</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                  </cac:TaxCategory>
               </cac:TaxSubtotal>';
        }
        if($comprobante->venta_icbper>0){
            $xml.='<cac:TaxSubtotal>
                      <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">'.$comprobante->venta_icbper.'</cbc:TaxAmount>
                      <cac:TaxCategory>
                         <cac:TaxScheme>
                            <cbc:ID>7152</cbc:ID>
                            <cbc:Name>ICBPER</cbc:Name>
                            <cbc:TaxTypeCode>OTH</cbc:TaxTypeCode>
                         </cac:TaxScheme>
                      </cac:TaxCategory>
                   </cac:TaxSubtotal>';
        }

        $total_antes_de_impuestos = $comprobante->venta_totalgravada + $comprobante->venta_totalexonerada + $comprobante->venta_totalinafecta;

        $xml.='</cac:TaxTotal>
         <cac:LegalMonetaryTotal>
            <cbc:LineExtensionAmount currencyID="'.$comprobante->abrstandar.'">'.$total_antes_de_impuestos.'</cbc:LineExtensionAmount>
            <cbc:TaxInclusiveAmount currencyID="'.$comprobante->abrstandar.'">'.$comprobante->venta_total.'</cbc:TaxInclusiveAmount>
            <cbc:PayableAmount currencyID="'.$comprobante->abrstandar.'">'.$comprobante->venta_total.'</cbc:PayableAmount>
         </cac:LegalMonetaryTotal>';
        $item = 1;
        foreach($detalle as $v){
            $xml.='<cac:InvoiceLine>
               <cbc:ID>'.$item.'</cbc:ID>
               <cbc:InvoicedQuantity unitCode="'.$v->medida_codigo_unidad.'">'.$v->venta_detalle_cantidad.'</cbc:InvoicedQuantity>
               <cbc:LineExtensionAmount currencyID="'.$comprobante->abrstandar.'">'.$v->venta_detalle_valor_total.'</cbc:LineExtensionAmount>
               <cac:PricingReference>';
            if($v->codigo == "21"){
                $xml.= '<cac:AlternativeConditionPrice>
                     <cbc:PriceAmount currencyID="'.$comprobante->abrstandar.'">'.$v->venta_detalle_precio_unitario.'</cbc:PriceAmount>
                     <cbc:PriceTypeCode>02</cbc:PriceTypeCode>
                  </cac:AlternativeConditionPrice>';
            }else {
                $xml .= '<cac:AlternativeConditionPrice>
                     <cbc:PriceAmount currencyID="' . $comprobante->abrstandar . '">' . $v->venta_detalle_precio_unitario . '</cbc:PriceAmount>
                     <cbc:PriceTypeCode>01</cbc:PriceTypeCode>
                  </cac:AlternativeConditionPrice>';
            }
            $xml.= '</cac:PricingReference>';

            $impuesto_items = ($v->venta_detalle_total_igv + $v->venta_detalle_total_icbper) * 1;

            $xml.= '<cac:TaxTotal>
                  <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">'.$impuesto_items.'</cbc:TaxAmount>
                  <cac:TaxSubtotal>
                     <cbc:TaxableAmount currencyID="'.$comprobante->abrstandar.'">'.$v->venta_detalle_valor_total.'</cbc:TaxableAmount>
                     <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">'.$v->venta_detalle_total_igv.'</cbc:TaxAmount>
                     <cac:TaxCategory>
                        <cbc:Percent>'.$v->venta_detalle_porcentaje_igv.'</cbc:Percent>
                        <cbc:TaxExemptionReasonCode>'.$v->codigo.'</cbc:TaxExemptionReasonCode>
                        <cac:TaxScheme>
                           <cbc:ID>'.$v->codigo_afectacion.'</cbc:ID>
                           <cbc:Name>'.$v->nombre_afectacion.'</cbc:Name>
                           <cbc:TaxTypeCode>'.$v->tipo_afectacion.'</cbc:TaxTypeCode>
                        </cac:TaxScheme>
                     </cac:TaxCategory>
                  </cac:TaxSubtotal>';

            if($v->venta_detalle_total_icbper > 0){
                $xml.= '<cac:TaxSubtotal>
                            <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">'.$v->venta_detalle_total_icbper.'</cbc:TaxAmount>
                            <cbc:BaseUnitMeasure unitCode="NIU">'.$v->venta_detalle_cantidad.'</cbc:BaseUnitMeasure>
                        <cac:TaxCategory>
                        <cbc:PerUnitAmount currencyID="PEN">'.$icbper.'</cbc:PerUnitAmount>
                            <cac:TaxScheme>
                                <cbc:ID>7152</cbc:ID>
                                <cbc:Name>ICBPER</cbc:Name>
                                <cbc:TaxTypeCode>OTH</cbc:TaxTypeCode>
                            </cac:TaxScheme>
                        </cac:TaxCategory>
                        </cac:TaxSubtotal>';
            }

            $xml.=
                '</cac:TaxTotal>';

            $xml.= '<cac:Item>
                      <cbc:Description><![CDATA['.$v->venta_detalle_nombre_producto.']]></cbc:Description>
                      <cac:SellersItemIdentification>
                         <cbc:ID>'.$v->id_pro.'</cbc:ID>
                      </cac:SellersItemIdentification>
                   </cac:Item>';

            if($v->codigo == "21"){
                $xml.= '<cac:Price>
                  <cbc:PriceAmount currencyID="'.$comprobante->abrstandar.'">0.00</cbc:PriceAmount>
               </cac:Price>
            </cac:InvoiceLine>';
            }else{
                $xml.= '<cac:Price>
                  <cbc:PriceAmount currencyID="'.$comprobante->abrstandar.'">'.$v->venta_detalle_valor_unitario.'</cbc:PriceAmount>
               </cac:Price>
            </cac:InvoiceLine>';
            }

            $item++;
        }

        $xml.="</Invoice>";

        $doc->loadXML($xml);
        $doc->save($nombrexml.'.XML');
    }

    public static function CrearXMLNotaCredito($nombrexml, $emisor, $cliente, $comprobante, $detalle, $descripcion_nota)
    {
        $doc = new \DOMDocument();
        $doc->formatOutput = FALSE;
        $doc->preserveWhiteSpace = TRUE;
        $doc->encoding = 'utf-8';

        /* TOTAL EN LETRAS - INICIO */
        $da = new NumeroALetras();
        $total_letras = $da->toInvoice($comprobante->venta_total,'2','soles');
        /* TOTAL EN LETRAS - FINAL */

        /* NOMBRE CLIENTE - INICIO */
        $razon_social = $cliente->id_tipo_documento == 4 ? $cliente->cliente_razonsocial : $cliente->cliente_nombre;
        /* NOMBRE CLIENTE - FINAL */

        $anho = date('Y');
        if($anho == "2021"){
            $icbper = "0.30";
        }elseif($anho == "2022"){
            $icbper = "0.40";
        }else{
            $icbper = "0.50";
        }


        $xml = '<?xml version="1.0" encoding="UTF-8"?>
      <CreditNote xmlns="urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
         <ext:UBLExtensions>
            <ext:UBLExtension>
               <ext:ExtensionContent />
            </ext:UBLExtension>
         </ext:UBLExtensions>
         <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
         <cbc:CustomizationID>2.0</cbc:CustomizationID>
         <cbc:ID>'.$comprobante->venta_serie.'-'.$comprobante->venta_correlativo.'</cbc:ID>
         <cbc:IssueDate>'.date('Y-m-d', strtotime($comprobante->venta_fecha)).'</cbc:IssueDate>
         <cbc:IssueTime>'.date('H:i:s', strtotime($comprobante->venta_fecha)).'</cbc:IssueTime>
         <cbc:Note languageLocaleID="1000"><![CDATA['.$total_letras.']]></cbc:Note>
         <cbc:DocumentCurrencyCode>'.$comprobante->abrstandar.'</cbc:DocumentCurrencyCode>
         <cac:DiscrepancyResponse>
            <cbc:ReferenceID>'.$comprobante->serie_modificar.'-'.$comprobante->correlativo_modificar.'</cbc:ReferenceID>
            <cbc:ResponseCode>'.$comprobante->venta_codigo_motivo_nota.'</cbc:ResponseCode>
            <cbc:Description>'.$descripcion_nota->tipo_nota_descripcion.'</cbc:Description>
         </cac:DiscrepancyResponse>
         <cac:BillingReference>
            <cac:InvoiceDocumentReference>
               <cbc:ID>'.$comprobante->serie_modificar.'-'.$comprobante->correlativo_modificar.'</cbc:ID>
               <cbc:DocumentTypeCode>'.$comprobante->tipo_documento_modificar.'</cbc:DocumentTypeCode>
            </cac:InvoiceDocumentReference>
         </cac:BillingReference>
         <cac:Signature>
            <cbc:ID>'.$emisor->empresa_ruc.'</cbc:ID>
            <cbc:Note><![CDATA['.$emisor->empresa_nombrecomercial.']]></cbc:Note>
            <cac:SignatoryParty>
               <cac:PartyIdentification>
                  <cbc:ID>'.$emisor->empresa_ruc.'</cbc:ID>
               </cac:PartyIdentification>
               <cac:PartyName>
                  <cbc:Name><![CDATA['.$emisor->empresa_razon_social.']]></cbc:Name>
               </cac:PartyName>
            </cac:SignatoryParty>
            <cac:DigitalSignatureAttachment>
               <cac:ExternalReference>
                  <cbc:URI>#SIGN-EMPRESA</cbc:URI>
               </cac:ExternalReference>
            </cac:DigitalSignatureAttachment>
         </cac:Signature>
         <cac:AccountingSupplierParty>
            <cac:Party>
               <cac:PartyIdentification>
                  <cbc:ID schemeID="6">'.$emisor->empresa_ruc.'</cbc:ID>
               </cac:PartyIdentification>
               <cac:PartyName>
                  <cbc:Name><![CDATA['.$emisor->empresa_nombrecomercial.']]></cbc:Name>
               </cac:PartyName>
               <cac:PartyLegalEntity>
                  <cbc:RegistrationName><![CDATA['.$emisor->empresa_razon_social.']]></cbc:RegistrationName>
                  <cac:RegistrationAddress>
                     <cbc:ID>'.$emisor->ubigeo_cod.'</cbc:ID>
                     <cbc:AddressTypeCode>0000</cbc:AddressTypeCode>
                     <cbc:CitySubdivisionName>NONE</cbc:CitySubdivisionName>
                      <cbc:CityName>'.$emisor->ubigeo_provincia.'</cbc:CityName>
                     <cbc:CountrySubentity>'.$emisor->ubigeo_departamento.'</cbc:CountrySubentity>
                     <cbc:District>'.$emisor->ubigeo_distrito.'</cbc:District>
                     <cac:AddressLine>
                        <cbc:Line><![CDATA['.$emisor->empresa_domiciliofiscal.']]></cbc:Line>
                     </cac:AddressLine>
                     <cac:Country>
                        <cbc:IdentificationCode>'.$emisor->empresa_pais.'</cbc:IdentificationCode>
                     </cac:Country>
                  </cac:RegistrationAddress>
               </cac:PartyLegalEntity>
            </cac:Party>
         </cac:AccountingSupplierParty>
         <cac:AccountingCustomerParty>
            <cac:Party>
               <cac:PartyIdentification>
                  <cbc:ID schemeID="'.$cliente->tipodocumento_codigo.'">'.$cliente->cliente_numero.'</cbc:ID>
               </cac:PartyIdentification>
               <cac:PartyLegalEntity>
                  <cbc:RegistrationName><![CDATA['.$razon_social.']]></cbc:RegistrationName>
                  <cac:RegistrationAddress>
                     <cac:AddressLine>
                        <cbc:Line><![CDATA['.$cliente->cliente_direccion.']]></cbc:Line>
                     </cac:AddressLine>
                     <cac:Country>
                        <cbc:IdentificationCode>PE</cbc:IdentificationCode>
                     </cac:Country>
                  </cac:RegistrationAddress>
               </cac:PartyLegalEntity>
            </cac:Party>
         </cac:AccountingCustomerParty>';
        if($comprobante->venta_codigo_motivo_nota == 13){
            $xml.='<cac:PaymentTerms>
                    <cbc:ID>FormaPago</cbc:ID>
                    <cbc:PaymentMeansID>Contado</cbc:PaymentMeansID>
                   </cac:PaymentTerms>';
        }


        $impuesto = $comprobante->venta_totaligv + $comprobante->venta_icbper;

        $xml.='<cac:TaxTotal>
            <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">'.$impuesto.'</cbc:TaxAmount>';
        if($comprobante->venta_totalgravada > 0) {
            $xml .= '<cac:TaxSubtotal>
               <cbc:TaxableAmount currencyID="' . $comprobante->abrstandar . '">' . $comprobante->venta_totalgravada . '</cbc:TaxableAmount>
               <cbc:TaxAmount currencyID="' . $comprobante->abrstandar . '">' . $comprobante->venta_totaligv . '</cbc:TaxAmount>
               <cac:TaxCategory>
                  <cac:TaxScheme>
                     <cbc:ID>1000</cbc:ID>
                     <cbc:Name>IGV</cbc:Name>
                     <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                  </cac:TaxScheme>
               </cac:TaxCategory>
            </cac:TaxSubtotal>';
        }

        if($comprobante->venta_totalexonerada>0){
            $xml.='<cac:TaxSubtotal>
                  <cbc:TaxableAmount currencyID="'.$comprobante->abrstandar.'">'.$comprobante->venta_totalexonerada.'</cbc:TaxableAmount>
                  <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">0.00</cbc:TaxAmount>
                  <cac:TaxCategory>
                     <cbc:ID schemeID="UN/ECE 5305" schemeName="Tax Category Identifier" schemeAgencyName="United Nations Economic Commission for Europe">E</cbc:ID>
                     <cac:TaxScheme>
                        <cbc:ID schemeID="UN/ECE 5153" schemeAgencyID="6">9997</cbc:ID>
                        <cbc:Name>EXO</cbc:Name>
                        <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                     </cac:TaxScheme>
                  </cac:TaxCategory>
               </cac:TaxSubtotal>';
        }
        if($comprobante->venta_totalinafecta>0){
            $xml.='<cac:TaxSubtotal>
                  <cbc:TaxableAmount currencyID="'.$comprobante->abrstandar.'">'.$comprobante->venta_totalinafecta.'</cbc:TaxableAmount>
                  <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">0.00</cbc:TaxAmount>
                  <cac:TaxCategory>
                     <cbc:ID schemeID="UN/ECE 5305" schemeName="Tax Category Identifier" schemeAgencyName="United Nations Economic Commission for Europe">E</cbc:ID>
                     <cac:TaxScheme>
                        <cbc:ID schemeID="UN/ECE 5153" schemeAgencyID="6">9998</cbc:ID>
                        <cbc:Name>INA</cbc:Name>
                        <cbc:TaxTypeCode>FRE</cbc:TaxTypeCode>
                     </cac:TaxScheme>
                  </cac:TaxCategory>
               </cac:TaxSubtotal>';
        }
        if($comprobante->venta_totalgratuita>0){
            $xml.='<cac:TaxSubtotal>
                  <cbc:TaxableAmount currencyID="'.$comprobante->abrstandar.'">'.$comprobante->venta_totalgratuita.'</cbc:TaxableAmount>
                  <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">0.00</cbc:TaxAmount>
                  <cac:TaxCategory>
                    <cbc:ID schemeID="UN/ECE 5305" schemeName="Tax Category Identifier" schemeAgencyName="United Nations Economic Commission for Europe">Z</cbc:ID>
                    <cac:TaxScheme>
                       <cbc:ID>9996</cbc:ID>
                       <cbc:Name>GRA</cbc:Name>
                       <cbc:TaxTypeCode>FRE</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                  </cac:TaxCategory>
               </cac:TaxSubtotal>';
        }
        if($comprobante->venta_icbper > 0){
            $xml.='<cac:TaxSubtotal>
                      <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">'.$comprobante->venta_icbper.'</cbc:TaxAmount>
                      <cac:TaxCategory>
                         <cac:TaxScheme>
                            <cbc:ID>7152</cbc:ID>
                            <cbc:Name>ICBPER</cbc:Name>
                            <cbc:TaxTypeCode>OTH</cbc:TaxTypeCode>
                         </cac:TaxScheme>
                      </cac:TaxCategory>
                   </cac:TaxSubtotal>';
        }

        $total_antes_de_impuestos = $comprobante->venta_totalgravada+$comprobante->venta_totalexonerada+$comprobante->venta_totalinafecta;

        $xml.='</cac:TaxTotal>
         <cac:LegalMonetaryTotal>
            <cbc:LineExtensionAmount currencyID="'.$comprobante->abrstandar.'">'.$total_antes_de_impuestos.'</cbc:LineExtensionAmount>
            <cbc:TaxInclusiveAmount currencyID="'.$comprobante->abrstandar.'">'.$comprobante->venta_total.'</cbc:TaxInclusiveAmount>
            <cbc:PayableAmount currencyID="'.$comprobante->abrstandar.'">'.$comprobante->venta_total.'</cbc:PayableAmount>
         </cac:LegalMonetaryTotal>';
        $item = 1;

        foreach($detalle as $v){
            $xml.='<cac:CreditNoteLine>
               <cbc:ID>'.$item.'</cbc:ID>
               <cbc:CreditedQuantity unitCode="'.$v->medida_codigo_unidad.'">'.$v->venta_detalle_cantidad.'</cbc:CreditedQuantity>
               <cbc:LineExtensionAmount currencyID="'.$comprobante->abrstandar.'">'.$v->venta_detalle_valor_total.'</cbc:LineExtensionAmount>
               <cac:PricingReference>';
            if($v->codigo == "21"){
                $xml.= '<cac:AlternativeConditionPrice>
                     <cbc:PriceAmount currencyID="'.$comprobante->abrstandar.'">'.$v->venta_detalle_precio_unitario.'</cbc:PriceAmount>
                     <cbc:PriceTypeCode>02</cbc:PriceTypeCode>
                  </cac:AlternativeConditionPrice>';
            }else {
                $xml .= '<cac:AlternativeConditionPrice>
                     <cbc:PriceAmount currencyID="' . $comprobante->abrstandar . '">' . $v->venta_detalle_precio_unitario . '</cbc:PriceAmount>
                     <cbc:PriceTypeCode>01</cbc:PriceTypeCode>
                  </cac:AlternativeConditionPrice>';
            }

            $impuesto_items = ($v->venta_detalle_total_igv + $v->venta_detalle_total_icbper) * 1;

            $xml.= '</cac:PricingReference>
               <cac:TaxTotal>
                  <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">'.$impuesto_items.'</cbc:TaxAmount>
                  <cac:TaxSubtotal>
                     <cbc:TaxableAmount currencyID="'.$comprobante->abrstandar.'">'.$v->venta_detalle_valor_total.'</cbc:TaxableAmount>
                     <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">'.$v->venta_detalle_total_igv.'</cbc:TaxAmount>
                     <cac:TaxCategory>
                        <cbc:Percent>'.$v->venta_detalle_porcentaje_igv.'</cbc:Percent>
                        <cbc:TaxExemptionReasonCode>'.$v->codigo.'</cbc:TaxExemptionReasonCode>
                        <cac:TaxScheme>
                           <cbc:ID>'.$v->codigo_afectacion.'</cbc:ID>
                           <cbc:Name>'.$v->nombre_afectacion.'</cbc:Name>
                           <cbc:TaxTypeCode>'.$v->tipo_afectacion.'</cbc:TaxTypeCode>
                        </cac:TaxScheme>
                     </cac:TaxCategory>
                  </cac:TaxSubtotal>';

            if($v->venta_detalle_total_icbper > 0){
                $xml.= '<cac:TaxSubtotal>
                            <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">'.$v->venta_detalle_total_icbper.'</cbc:TaxAmount>
                            <cbc:BaseUnitMeasure unitCode="NIU">'.$v->venta_detalle_cantidad.'</cbc:BaseUnitMeasure>
                        <cac:TaxCategory>
                        <cbc:PerUnitAmount currencyID="PEN">'.$icbper.'</cbc:PerUnitAmount>
                            <cac:TaxScheme>
                                <cbc:ID>7152</cbc:ID>
                                <cbc:Name>ICBPER</cbc:Name>
                                <cbc:TaxTypeCode>OTH</cbc:TaxTypeCode>
                            </cac:TaxScheme>
                        </cac:TaxCategory>
                        </cac:TaxSubtotal>';
            }

            $xml.=
                '</cac:TaxTotal>';

            $xml.= '<cac:Item>
                      <cbc:Description><![CDATA['.$v->venta_detalle_nombre_producto.']]></cbc:Description>
                      <cac:SellersItemIdentification>
                         <cbc:ID>'.$v->id_pro.'</cbc:ID>
                      </cac:SellersItemIdentification>
                   </cac:Item>';

            if($v->codigo == "21"){
                $xml.= '<cac:Price>
                  <cbc:PriceAmount currencyID="'.$comprobante->abrstandar.'">0.00</cbc:PriceAmount>
               </cac:Price>
            </cac:CreditNoteLine>';
            }else{
                $xml.= '<cac:Price>
                  <cbc:PriceAmount currencyID="'.$comprobante->abrstandar.'">'.$v->venta_detalle_valor_unitario.'</cbc:PriceAmount>
               </cac:Price>
            </cac:CreditNoteLine>';
            }

            $item++;
        }
        $xml.='</CreditNote>';

        $doc->loadXML($xml);
        $doc->save($nombrexml.'.XML');
    }

    public static function CrearXMLNotaDebito($nombrexml, $emisor, $cliente, $comprobante, $detalle, $descripcion_nota)
    {

        $doc = new \DOMDocument();
        $doc->formatOutput = FALSE;
        $doc->preserveWhiteSpace = TRUE;
        $doc->encoding = 'utf-8';

        /* TOTAL EN LETRAS - INICIO */
        $da = new NumeroALetras();
        $total_letras = $da->toInvoice($comprobante->venta_total,'2','soles');
        /* TOTAL EN LETRAS - FINAL */

        /* NOMBRE DEL CLIENTE - INICIO */
        $razon_social = $cliente->id_tipo_documento == 4 ? $cliente->cliente_razonsocial : $cliente->cliente_nombre;
        /* NOMBRE DEL CLIENTE - FINAL */


        $anho = date('Y');
        if($anho == "2021"){
            $icbper = "0.30";
        }elseif($anho == "2022"){
            $icbper = "0.40";
        }else{
            $icbper = "0.50";
        }


        $xml = '<?xml version="1.0" encoding="UTF-8"?>
      <DebitNote xmlns="urn:oasis:names:specification:ubl:schema:xsd:DebitNote-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
         <ext:UBLExtensions>
            <ext:UBLExtension>
               <ext:ExtensionContent />
            </ext:UBLExtension>
         </ext:UBLExtensions>
         <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
         <cbc:CustomizationID>2.0</cbc:CustomizationID>
         <cbc:ID>'.$comprobante->venta_serie.'-'.$comprobante->venta_correlativo.'</cbc:ID>
         <cbc:IssueDate>'.date('Y-m-d', strtotime($comprobante->venta_fecha)).'</cbc:IssueDate>
         <cbc:IssueTime>'.date('H:i:s', strtotime($comprobante->venta_fecha)).'</cbc:IssueTime>
         <cbc:Note languageLocaleID="1000"><![CDATA['.$total_letras.']]></cbc:Note>
         <cbc:DocumentCurrencyCode>'.$comprobante->abrstandar.'</cbc:DocumentCurrencyCode>
         <cac:DiscrepancyResponse>
            <cbc:ReferenceID>'.$comprobante->serie_modificar.'-'.$comprobante->correlativo_modificar.'</cbc:ReferenceID>
            <cbc:ResponseCode>'.$comprobante->venta_codigo_motivo_nota.'</cbc:ResponseCode>
            <cbc:Description>'.$descripcion_nota->tipo_nota_descripcion.'</cbc:Description>
         </cac:DiscrepancyResponse>
         <cac:BillingReference>
            <cac:InvoiceDocumentReference>
               <cbc:ID>'.$comprobante->serie_modificar.'-'.$comprobante->correlativo_modificar.'</cbc:ID>
               <cbc:DocumentTypeCode>'.$comprobante->tipo_documento_modificar.'</cbc:DocumentTypeCode>
            </cac:InvoiceDocumentReference>
         </cac:BillingReference>
         <cac:Signature>
            <cbc:ID>'.$emisor->empresa_ruc.'</cbc:ID>
            <cbc:Note><![CDATA['.$emisor->empresa_nombrecomercial.']]></cbc:Note>
            <cac:SignatoryParty>
               <cac:PartyIdentification>
                  <cbc:ID>'.$emisor->empresa_ruc.'</cbc:ID>
               </cac:PartyIdentification>
               <cac:PartyName>
                  <cbc:Name><![CDATA['.$emisor->empresa_razon_social.']]></cbc:Name>
               </cac:PartyName>
            </cac:SignatoryParty>
            <cac:DigitalSignatureAttachment>
               <cac:ExternalReference>
                  <cbc:URI>#SIGN-EMPRESA</cbc:URI>
               </cac:ExternalReference>
            </cac:DigitalSignatureAttachment>
         </cac:Signature>
         <cac:AccountingSupplierParty>
            <cac:Party>
               <cac:PartyIdentification>
                  <cbc:ID schemeID="6">'.$emisor->empresa_ruc.'</cbc:ID>
               </cac:PartyIdentification>
               <cac:PartyName>
                  <cbc:Name><![CDATA['.$emisor->empresa_nombrecomercial.']]></cbc:Name>
               </cac:PartyName>
               <cac:PartyLegalEntity>
                  <cbc:RegistrationName><![CDATA['.$emisor->empresa_razon_social.']]></cbc:RegistrationName>
                  <cac:RegistrationAddress>
                     <cbc:ID>'.$emisor->ubigeo_cod.'</cbc:ID>
                     <cbc:AddressTypeCode>0000</cbc:AddressTypeCode>
                     <cbc:CitySubdivisionName>NONE</cbc:CitySubdivisionName>
                     <cbc:CityName>'.$emisor->ubigeo_provincia.'</cbc:CityName>
                     <cbc:CountrySubentity>'.$emisor->ubigeo_departamento.'</cbc:CountrySubentity>
                     <cbc:District>'.$emisor->ubigeo_distrito.'</cbc:District>
                     <cac:AddressLine>
                        <cbc:Line><![CDATA['.$emisor->empresa_domiciliofiscal.']]></cbc:Line>
                     </cac:AddressLine>
                     <cac:Country>
                        <cbc:IdentificationCode>'.$emisor->empresa_pais.'</cbc:IdentificationCode>
                     </cac:Country>
                  </cac:RegistrationAddress>
               </cac:PartyLegalEntity>
            </cac:Party>
         </cac:AccountingSupplierParty>
         <cac:AccountingCustomerParty>
            <cac:Party>
               <cac:PartyIdentification>
                  <cbc:ID schemeID="'.$cliente->tipodocumento_codigo.'">'.$cliente->cliente_numero.'</cbc:ID>
               </cac:PartyIdentification>
               <cac:PartyLegalEntity>
                  <cbc:RegistrationName><![CDATA['.$razon_social.']]></cbc:RegistrationName>
                  <cac:RegistrationAddress>
                     <cac:AddressLine>
                        <cbc:Line><![CDATA['.$cliente->cliente_direccion.']]></cbc:Line>
                     </cac:AddressLine>
                     <cac:Country>
                        <cbc:IdentificationCode>PE</cbc:IdentificationCode>
                     </cac:Country>
                  </cac:RegistrationAddress>
               </cac:PartyLegalEntity>
            </cac:Party>
         </cac:AccountingCustomerParty>';

        $xml.='<cac:PaymentTerms>
                    <cbc:ID>FormaPago</cbc:ID>
                    <cbc:PaymentMeansID>Contado</cbc:PaymentMeansID>
                   </cac:PaymentTerms>';

        $impuesto = $comprobante->venta_totaligv + $comprobante->venta_icbper;

        $xml.='<cac:TaxTotal>
                <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">'.$impuesto.'</cbc:TaxAmount>';
        if($comprobante->venta_totalgravada > 0) {
            $xml .= '<cac:TaxSubtotal>
                   <cbc:TaxableAmount currencyID="' . $comprobante->abrstandar . '">' . $comprobante->venta_totalgravada . '</cbc:TaxableAmount>
                   <cbc:TaxAmount currencyID="' . $comprobante->abrstandar . '">' . $comprobante->venta_totaligv . '</cbc:TaxAmount>
                   <cac:TaxCategory>
                      <cac:TaxScheme>
                         <cbc:ID>1000</cbc:ID>
                         <cbc:Name>IGV</cbc:Name>
                         <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                      </cac:TaxScheme>
                   </cac:TaxCategory>
                </cac:TaxSubtotal>';
        }

        if($comprobante->venta_totalexonerada>0){
            $xml.='<cac:TaxSubtotal>
                  <cbc:TaxableAmount currencyID="'.$comprobante->abrstandar.'">'.$comprobante->venta_totalexonerada.'</cbc:TaxableAmount>
                  <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">0.00</cbc:TaxAmount>
                  <cac:TaxCategory>
                     <cbc:ID schemeID="UN/ECE 5305" schemeName="Tax Category Identifier" schemeAgencyName="United Nations Economic Commission for Europe">E</cbc:ID>
                     <cac:TaxScheme>
                        <cbc:ID schemeID="UN/ECE 5153" schemeAgencyID="6">9997</cbc:ID>
                        <cbc:Name>EXO</cbc:Name>
                        <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                     </cac:TaxScheme>
                  </cac:TaxCategory>
               </cac:TaxSubtotal>';
        }
        if($comprobante->venta_totalinafecta>0){
            $xml.='<cac:TaxSubtotal>
                  <cbc:TaxableAmount currencyID="'.$comprobante->abrstandar.'">'.$comprobante->venta_totalinafecta.'</cbc:TaxableAmount>
                  <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">0.00</cbc:TaxAmount>
                  <cac:TaxCategory>
                     <cbc:ID schemeID="UN/ECE 5305" schemeName="Tax Category Identifier" schemeAgencyName="United Nations Economic Commission for Europe">E</cbc:ID>
                     <cac:TaxScheme>
                        <cbc:ID schemeID="UN/ECE 5153" schemeAgencyID="6">9998</cbc:ID>
                        <cbc:Name>INA</cbc:Name>
                        <cbc:TaxTypeCode>FRE</cbc:TaxTypeCode>
                     </cac:TaxScheme>
                  </cac:TaxCategory>
               </cac:TaxSubtotal>';
        }
        if($comprobante->venta_totalgratuita>0){
            $xml.='<cac:TaxSubtotal>
                  <cbc:TaxableAmount currencyID="'.$comprobante->abrstandar.'">'.$comprobante->venta_totalgratuita.'</cbc:TaxableAmount>
                  <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">0.00</cbc:TaxAmount>
                  <cac:TaxCategory>
                    <cbc:ID schemeID="UN/ECE 5305" schemeName="Tax Category Identifier" schemeAgencyName="United Nations Economic Commission for Europe">Z</cbc:ID>
                    <cac:TaxScheme>
                       <cbc:ID>9996</cbc:ID>
                       <cbc:Name>GRA</cbc:Name>
                       <cbc:TaxTypeCode>FRE</cbc:TaxTypeCode>
                    </cac:TaxScheme>
                  </cac:TaxCategory>
               </cac:TaxSubtotal>';
        }
        if($comprobante->venta_icbper>0){
            $xml.='<cac:TaxSubtotal>
                      <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">'.$comprobante->venta_icbper.'</cbc:TaxAmount>
                      <cac:TaxCategory>
                         <cac:TaxScheme>
                            <cbc:ID>7152</cbc:ID>
                            <cbc:Name>ICBPER</cbc:Name>
                            <cbc:TaxTypeCode>OTH</cbc:TaxTypeCode>
                         </cac:TaxScheme>
                      </cac:TaxCategory>
                   </cac:TaxSubtotal>';
        }

//        $total_antes_de_impuestos = $comprobante->venta_totalgravada+$comprobante->venta_totalexonerada+$comprobante->venta_totalinafecta;

        $xml.='</cac:TaxTotal>
         <cac:RequestedMonetaryTotal>
            <cbc:PayableAmount currencyID="'.$comprobante->abrstandar.'">'.$comprobante->venta_total.'</cbc:PayableAmount>
         </cac:RequestedMonetaryTotal>';
        $item = 1;

        foreach($detalle as $v){
            $xml.='<cac:DebitNoteLine>
               <cbc:ID>'.$item.'</cbc:ID>
               <cbc:DebitedQuantity unitCode="'.$v->medida_codigo_unidad.'">'.$v->venta_detalle_cantidad.'</cbc:DebitedQuantity>
               <cbc:LineExtensionAmount currencyID="'.$comprobante->abrstandar.'">'.$v->venta_detalle_valor_total.'</cbc:LineExtensionAmount>
               <cac:PricingReference>';
            if($v->codigo == "21"){
                $xml.= '<cac:AlternativeConditionPrice>
                     <cbc:PriceAmount currencyID="'.$comprobante->abrstandar.'">'.$v->venta_detalle_precio_unitario.'</cbc:PriceAmount>
                     <cbc:PriceTypeCode>02</cbc:PriceTypeCode>
                  </cac:AlternativeConditionPrice>';
            }else {
                $xml .= '<cac:AlternativeConditionPrice>
                     <cbc:PriceAmount currencyID="' . $comprobante->abrstandar . '">' . $v->venta_detalle_precio_unitario . '</cbc:PriceAmount>
                     <cbc:PriceTypeCode>01</cbc:PriceTypeCode>
                  </cac:AlternativeConditionPrice>';
            }

            $impuesto_items = ($v->venta_detalle_total_igv) + ($v->venta_detalle_total_icbper) * 1;

            $xml.= '</cac:PricingReference>
               <cac:TaxTotal>
                  <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">'.$impuesto_items.'</cbc:TaxAmount>
                  <cac:TaxSubtotal>
                     <cbc:TaxableAmount currencyID="'.$comprobante->abrstandar.'">'.$v->venta_detalle_valor_total.'</cbc:TaxableAmount>
                     <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">'.$v->venta_detalle_total_igv.'</cbc:TaxAmount>
                     <cac:TaxCategory>';
            if($v->codigo == "10"){
                $xml.= '<cbc:Percent>'.$v->venta_detalle_porcentaje_igv.'</cbc:Percent>';
            }else{
                $xml.= '<cbc:Percent>0.00</cbc:Percent>';
            }
            $xml.= '<cbc:TaxExemptionReasonCode>'.$v->codigo.'</cbc:TaxExemptionReasonCode>
                        <cac:TaxScheme>
                           <cbc:ID>'.$v->codigo_afectacion.'</cbc:ID>
                           <cbc:Name>'.$v->nombre_afectacion.'</cbc:Name>
                           <cbc:TaxTypeCode>'.$v->tipo_afectacion.'</cbc:TaxTypeCode>
                        </cac:TaxScheme>
                     </cac:TaxCategory>
                  </cac:TaxSubtotal>';

            if($v->venta_detalle_total_icbper > 0){

                $xml.= '<cac:TaxSubtotal>
                            <cbc:TaxAmount currencyID="'.$comprobante->abrstandar.'">'.$v->venta_detalle_total_icbper.'</cbc:TaxAmount>
                            <cbc:BaseUnitMeasure unitCode="NIU">'.$v->venta_detalle_cantidad.'</cbc:BaseUnitMeasure>
                        <cac:TaxCategory>
                        <cbc:PerUnitAmount currencyID="PEN">'.$icbper.'</cbc:PerUnitAmount>
                            <cac:TaxScheme>
                                <cbc:ID>7152</cbc:ID>
                                <cbc:Name>ICBPER</cbc:Name>
                                <cbc:TaxTypeCode>OTH</cbc:TaxTypeCode>
                            </cac:TaxScheme>
                        </cac:TaxCategory>
                        </cac:TaxSubtotal>';
            }

            $xml.=
                '</cac:TaxTotal>';

            $xml.= '<cac:Item>
                      <cbc:Description><![CDATA['.$v->venta_detalle_nombre_producto.']]></cbc:Description>
                      <cac:SellersItemIdentification>
                         <cbc:ID>'.$v->id_pro.'</cbc:ID>
                      </cac:SellersItemIdentification>
                   </cac:Item>';

            if($v->codigo == "21"){
                $xml.= '<cac:Price>
                  <cbc:PriceAmount currencyID="'.$comprobante->abrstandar.'">0.00</cbc:PriceAmount>
               </cac:Price>
            </cac:CreditNoteLine>';
            }else{
                $xml.= '<cac:Price>
                  <cbc:PriceAmount currencyID="'.$comprobante->abrstandar.'">'.$v->venta_detalle_valor_unitario.'</cbc:PriceAmount>
               </cac:Price>
            </cac:DebitNoteLine>';
            }

            $item++;
        }
        $xml.='</DebitNote>';

        $doc->loadXML($xml);
        $doc->save($nombrexml.'.XML');
    }
    public static function CrearXMLResumenDocumentos($emisor, $cabecera, $detalle, $nombrexml, $fecha_emision)
    {
        $doc = new \DOMDocument();
        $doc->formatOutput = FALSE;
        $doc->preserveWhiteSpace = TRUE;
        $doc->encoding = 'utf-8';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
           <SummaryDocuments xmlns="urn:sunat:names:specification:ubl:peru:schema:xsd:SummaryDocuments-1" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2" xmlns:sac="urn:sunat:names:specification:ubl:peru:schema:xsd:SunatAggregateComponents-1" xmlns:qdt="urn:oasis:names:specification:ubl:schema:xsd:QualifiedDatatypes-2" xmlns:udt="urn:un:unece:uncefact:data:specification:UnqualifiedDataTypesSchemaModule:2">
          <ext:UBLExtensions>
              <ext:UBLExtension>
                  <ext:ExtensionContent />
              </ext:UBLExtension>
          </ext:UBLExtensions>
          <cbc:UBLVersionID>2.0</cbc:UBLVersionID>
          <cbc:CustomizationID>1.1</cbc:CustomizationID>
          <cbc:ID>'.$cabecera['tipocomp'].'-'.$cabecera['serie'].'-'.$cabecera['correlativo'].'</cbc:ID>
          <cbc:ReferenceDate>'.$fecha_emision.'</cbc:ReferenceDate>
          <cbc:IssueDate>'.date('Y-m-d').'</cbc:IssueDate>
          <cac:Signature>
              <cbc:ID>'.$cabecera['tipocomp'].'-'.$cabecera['serie'].'-'.$cabecera['correlativo'].'</cbc:ID>
              <cac:SignatoryParty>
                  <cac:PartyIdentification>
                      <cbc:ID>'.$emisor->empresa_ruc.'</cbc:ID>
                  </cac:PartyIdentification>
                  <cac:PartyName>
                      <cbc:Name><![CDATA['.$emisor->empresa_nombrecomercial.']]></cbc:Name>
                  </cac:PartyName>
              </cac:SignatoryParty>
              <cac:DigitalSignatureAttachment>
                  <cac:ExternalReference>
                      <cbc:URI>'.$cabecera['tipocomp'].'-'.$cabecera['serie'].'-'.$cabecera['correlativo'].'</cbc:URI>
                  </cac:ExternalReference>
              </cac:DigitalSignatureAttachment>
          </cac:Signature>
          <cac:AccountingSupplierParty>
              <cbc:CustomerAssignedAccountID>'.$emisor->empresa_ruc.'</cbc:CustomerAssignedAccountID>
              <cbc:AdditionalAccountID>6</cbc:AdditionalAccountID>
              <cac:Party>
                  <cac:PartyLegalEntity>
                      <cbc:RegistrationName><![CDATA['.$emisor->empresa_nombrecomercial.']]></cbc:RegistrationName>
                  </cac:PartyLegalEntity>
              </cac:Party>
          </cac:AccountingSupplierParty>';
        $item = 1;
        foreach ($detalle as $v) {

            $xml.='<sac:SummaryDocumentsLine>
                 <cbc:LineID>'.$item.'</cbc:LineID>
                 <cbc:DocumentTypeCode>'.$v->venta_tipo.'</cbc:DocumentTypeCode>
                 <cbc:ID>'.$v->venta_serie.'-'.$v->venta_correlativo.'</cbc:ID>';

            if($v->cliente_numero != '00000000'){
                $xml .= '<cac:AccountingCustomerParty>
                             <cbc:CustomerAssignedAccountID>'.$v->cliente_numero.'</cbc:CustomerAssignedAccountID>
                             <cbc:AdditionalAccountID>'.$v->tipodocumento_codigo.'</cbc:AdditionalAccountID>
                         </cac:AccountingCustomerParty>';
            }

            if($v->venta_tipo == "07" || $v->venta_tipo == "08"){
                $xml .= '<cac:BillingReference>
                         <cac:InvoiceDocumentReference>
                            <cbc:ID>'.$v->serie_modificar.'-'.$v->correlativo_modificar.'</cbc:ID>
                            <cbc:DocumentTypeCode>'.$v->tipo_documento_modificar.'</cbc:DocumentTypeCode>
                         </cac:InvoiceDocumentReference>
                     </cac:BillingReference>';
            }

            $xml.= '<cac:Status>
                    <cbc:ConditionCode>'.$v->venta_condicion_resumen.'</cbc:ConditionCode>
                 </cac:Status>
                 <sac:TotalAmount currencyID="'.$v->abrstandar.'">'.number_format((float)$v->venta_total, 2, '.', '').'</sac:TotalAmount>';

            if ((float)$v->venta_totalgravada > 0) {
                $xml.='<sac:BillingPayment>
                           <cbc:PaidAmount currencyID="'.$v->abrstandar.'">'.$v->venta_totalgravada.'</cbc:PaidAmount>
                           <cbc:InstructionID>01</cbc:InstructionID>
                       </sac:BillingPayment>';
            }
            if((float)$v->venta_totalexonerada > 0){
                $xml.=
                    '<sac:BillingPayment>
                           <cbc:PaidAmount currencyID="'.$v->abrstandar.'">'.$v->venta_totalexonerada.'</cbc:PaidAmount>
                           <cbc:InstructionID>02</cbc:InstructionID>
                       </sac:BillingPayment>';
            }
            if((float)$v->venta_totalinafecta > 0){
                $xml.=
                    '<sac:BillingPayment>
                           <cbc:PaidAmount currencyID="'.$v->abrstandar.'">'.$v->venta_totalinafecta.'</cbc:PaidAmount>
                           <cbc:InstructionID>03</cbc:InstructionID>
                       </sac:BillingPayment>';
            }
            if((float)$v->venta_totalgratuita > 0){
                $xml.=
                    '<sac:BillingPayment>
                           <cbc:PaidAmount currencyID="'.$v->abrstandar.'">'.$v->venta_totalgratuita.'</cbc:PaidAmount>
                           <cbc:InstructionID>05</cbc:InstructionID>
                       </sac:BillingPayment>';
            }

            $igvPercent = $v->venta_porcentaje_igv;
            $igvMonto = ((float)$v->venta_totalgravada > 0) ? (float)$v->venta_totaligv : 0.00;

            $xml .= '<cac:TaxTotal>
                <cbc:TaxAmount currencyID="'.$v->abrstandar.'">'.number_format($igvMonto, 2, '.', '').'</cbc:TaxAmount>';

            // IGV (1000) SIEMPRE (para evitar error 2278), aunque sea 0.00
            $xml .= '<cac:TaxSubtotal>
                <cbc:TaxAmount currencyID="'.$v->abrstandar.'">'.number_format($igvMonto, 2, '.', '').'</cbc:TaxAmount>
                    <cac:TaxCategory>
                        <cbc:Percent>'.number_format($igvPercent, 2, '.', '').'</cbc:Percent>
                        <cac:TaxScheme>
                            <cbc:ID>1000</cbc:ID>
                            <cbc:Name>IGV</cbc:Name>
                            <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                        </cac:TaxScheme>
                    </cac:TaxCategory>
                </cac:TaxSubtotal>';
            if ((float)$v->venta_totalexonerada > 0) {
                $xml .= '<cac:TaxSubtotal>
                    <cbc:TaxAmount currencyID="'.$v->abrstandar.'">0.00</cbc:TaxAmount>
                    <cac:TaxCategory>
                        <cac:TaxScheme>
                            <cbc:ID>9997</cbc:ID>
                            <cbc:Name>EXO</cbc:Name>
                            <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                        </cac:TaxScheme>
                    </cac:TaxCategory>
                </cac:TaxSubtotal>';
            }

            if ((float)$v->venta_totalinafecta > 0) {
                $xml .= '<cac:TaxSubtotal>
                            <cbc:TaxAmount currencyID="'.$v->abrstandar.'">0.00</cbc:TaxAmount>
                            <cac:TaxCategory>
                                <cac:TaxScheme>
                                    <cbc:ID>9998</cbc:ID>
                                    <cbc:Name>INA</cbc:Name>
                                    <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                                </cac:TaxScheme>
                            </cac:TaxCategory>
                        </cac:TaxSubtotal>';
            }

            if ((float)$v->venta_totalgratuita > 0) {
                $xml .= '<cac:TaxSubtotal>
                            <cbc:TaxAmount currencyID="'.$v->abrstandar.'">0.00</cbc:TaxAmount>
                            <cac:TaxCategory>
                                <cac:TaxScheme>
                                    <cbc:ID>9996</cbc:ID>
                                    <cbc:Name>GRA</cbc:Name>
                                    <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                                </cac:TaxScheme>
                            </cac:TaxCategory>
                        </cac:TaxSubtotal>';
            }

            $xml .= '</cac:TaxTotal>';
            $xml .='</sac:SummaryDocumentsLine>';
            $item++;
        }

        $xml.='</SummaryDocuments>';

        $doc->loadXML($xml);
        $doc->save($nombrexml.'.XML');
    }

    public static function CrearXmlBajaDocumentos($emisor, $cabecera, $detalle, $nombrexml)
    {
        $doc = new \DOMDocument();
        $doc->formatOutput = FALSE;
        $doc->preserveWhiteSpace = TRUE;
        $doc->encoding = 'utf-8';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <VoidedDocuments xmlns="urn:sunat:names:specification:ubl:peru:schema:xsd:VoidedDocuments-1" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2" xmlns:sac="urn:sunat:names:specification:ubl:peru:schema:xsd:SunatAggregateComponents-1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
          <ext:UBLExtensions>
              <ext:UBLExtension>
                  <ext:ExtensionContent     />
              </ext:UBLExtension>
          </ext:UBLExtensions>
          <cbc:UBLVersionID>2.0</cbc:UBLVersionID>
          <cbc:CustomizationID>1.0</cbc:CustomizationID>
          <cbc:ID>'.$cabecera['tipocomp'].'-'.$cabecera['serie'].'-'.$cabecera['correlativo'].'</cbc:ID>
          <cbc:ReferenceDate>'.date('Y-m-d', strtotime($detalle->venta_fecha)).'</cbc:ReferenceDate>
          <cbc:IssueDate>'.date('Y-m-d').'</cbc:IssueDate>
          <cac:Signature>
              <cbc:ID>'.$emisor->empresa_ruc.'</cbc:ID>
              <cac:SignatoryParty>
                  <cac:PartyIdentification>
                      <cbc:ID>'.$emisor->empresa_ruc.'</cbc:ID>
                  </cac:PartyIdentification>
                  <cac:PartyName>
                      <cbc:Name><![CDATA['.$emisor->empresa_razon_social.']]></cbc:Name>
                  </cac:PartyName>
              </cac:SignatoryParty>
              <cac:DigitalSignatureAttachment>
                  <cac:ExternalReference>
                      <cbc:URI>#signatureKG</cbc:URI>
                  </cac:ExternalReference>
              </cac:DigitalSignatureAttachment>
          </cac:Signature>
          <cac:AccountingSupplierParty>
              <cbc:CustomerAssignedAccountID>'.$emisor->empresa_ruc.'</cbc:CustomerAssignedAccountID>
              <cbc:AdditionalAccountID>6</cbc:AdditionalAccountID>
              <cac:Party>
                  <cac:PartyLegalEntity>
                      <cbc:RegistrationName><![CDATA['.$emisor->empresa_razon_social.']]></cbc:RegistrationName>
                  </cac:PartyLegalEntity>
              </cac:Party>
          </cac:AccountingSupplierParty>';


        $xml.='<sac:VoidedDocumentsLine>
                 <cbc:LineID>1</cbc:LineID>
                 <cbc:DocumentTypeCode>'.$detalle->venta_tipo.'</cbc:DocumentTypeCode>
                 <sac:DocumentSerialID>'.$detalle->venta_serie.'</sac:DocumentSerialID>
                 <sac:DocumentNumberID>'.$detalle->venta_correlativo.'</sac:DocumentNumberID>
                 <sac:VoidReasonDescription><![CDATA[Error en Documento]]></sac:VoidReasonDescription>
             </sac:VoidedDocumentsLine>';


        $xml.='</VoidedDocuments>';

        $doc->loadXML($xml);
        $doc->save($nombrexml.'.XML');
    }

    // ── Guía de Remisión Electrónica — UBL 2.1 SUNAT ─────────
    public static function CrearXMLGuiaRemision($nombrexml, $emisor, $guia, $detalle): void
    {
        $doc = new \DOMDocument();
        $doc->formatOutput = FALSE;
        $doc->preserveWhiteSpace = TRUE;
        $doc->encoding = 'utf-8';

        $motivoLabel = match($guia->guia_motivo_traslado) {
            '01' => 'VENTA',
            '02' => 'COMPRA',
            '03' => 'DEVOLUCIÓN',
            '04' => 'TRASLADO ENTRE ESTABLECIMIENTOS',
            '08' => 'IMPORTACIÓN',
            '09' => 'EXPORTACIÓN',
            '18' => 'TRASLADO ENTRE LOCALES',
            default => 'OTROS',
        };

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<DespatchAdvice xmlns="urn:oasis:names:specification:ubl:schema:xsd:DespatchAdvice-2"
    xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
    xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
    xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
    xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
    <ext:UBLExtensions>
        <ext:UBLExtension>
            <ext:ExtensionContent/>
        </ext:UBLExtension>
    </ext:UBLExtensions>
    <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
    <cbc:CustomizationID>2.0</cbc:CustomizationID>
    <cbc:ID>' . $guia->guia_serie . '-' . str_pad($guia->guia_correlativo, 8, '0', STR_PAD_LEFT) . '</cbc:ID>
    <cbc:IssueDate>' . $guia->guia_fecha_emision . '</cbc:IssueDate>
    <cbc:IssueTime>' . date('H:i:s') . '</cbc:IssueTime>
    <cbc:DespatchAdviceTypeCode listAgencyName="PE:SUNAT" listName="Tipo de Documento" listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01">09</cbc:DespatchAdviceTypeCode>
    <cbc:Note><![CDATA[' . $motivoLabel . ']]></cbc:Note>
    <cac:Signature>
        <cbc:ID>' . $emisor->empresa_ruc . '</cbc:ID>
        <cbc:Note><![CDATA[' . $emisor->empresa_nombrecomercial . ']]></cbc:Note>
        <cac:SignatoryParty>
            <cac:PartyIdentification>
                <cbc:ID>' . $emisor->empresa_ruc . '</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyName>
                <cbc:Name><![CDATA[' . $emisor->empresa_razon_social . ']]></cbc:Name>
            </cac:PartyName>
        </cac:SignatoryParty>
        <cac:DigitalSignatureAttachment>
            <cac:ExternalReference>
                <cbc:URI>#SIGN-EMPRESA</cbc:URI>
            </cac:ExternalReference>
        </cac:DigitalSignatureAttachment>
    </cac:Signature>
    <cac:AccountingSupplierParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="6" schemeName="SUNAT:Identificador De Documento De Identidad" schemeAgencyName="PE:SUNAT" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">' . $emisor->empresa_ruc . '</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[' . $emisor->empresa_razon_social . ']]></cbc:RegistrationName>
                <cac:RegistrationAddress>
                    <cbc:ID schemeAgencyName="PE:INEI" schemeName="Ubigeos">' . ($emisor->empresa_ubigeo ?? '150101') . '</cbc:ID>
                    <cbc:AddressTypeCode>0000</cbc:AddressTypeCode>
                    <cbc:CityName><![CDATA[' . ($emisor->empresa_departamento ?? 'LIMA') . ']]></cbc:CityName>
                    <cbc:StreetName><![CDATA[' . ($emisor->empresa_domiciliofiscal ?? '') . ']]></cbc:StreetName>
                </cac:RegistrationAddress>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingSupplierParty>
    <cac:DeliveryCustomerParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="' . $guia->guia_dest_tipo_doc . '" schemeName="SUNAT:Identificador De Documento De Identidad" schemeAgencyName="PE:SUNAT" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">' . $guia->guia_dest_numero_doc . '</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[' . $guia->guia_dest_nombre . ']]></cbc:RegistrationName>
                <cac:RegistrationAddress>
                    <cbc:ID schemeAgencyName="PE:INEI" schemeName="Ubigeos">' . ($guia->guia_llegada_ubigeo ?? '150101') . '</cbc:ID>
                    <cbc:StreetName><![CDATA[' . $guia->guia_dest_direccion . ']]></cbc:StreetName>
                </cac:RegistrationAddress>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:DeliveryCustomerParty>';

        // Transportista (modalidad pública)
        if ($guia->guia_modalidad_traslado === '01' && $guia->guia_transportista_ruc) {
            $xml .= '
    <cac:SellerSupplierParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="6" schemeName="SUNAT:Identificador De Documento De Identidad" schemeAgencyName="PE:SUNAT" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">' . $guia->guia_transportista_ruc . '</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName><![CDATA[' . $guia->guia_transportista_nombre . ']]></cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:SellerSupplierParty>';
        }

        $xml .= '
    <cac:Shipment>
        <cbc:ID>IDSHIPMENT</cbc:ID>
        <cbc:HandlingCode listAgencyName="PE:SUNAT" listName="Motivo de traslado" listURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo20">' . $guia->guia_motivo_traslado . '</cbc:HandlingCode>
        <cbc:HandlingInstructions><![CDATA[' . ($guia->guia_observaciones ?? $motivoLabel) . ']]></cbc:HandlingInstructions>
        <cbc:Information>' . $guia->guia_modalidad_traslado . '</cbc:Information>
        <cbc:GrossWeightMeasure unitCode="KGM">' . number_format($guia->guia_peso_bruto, 3, '.', '') . '</cbc:GrossWeightMeasure>
        <cac:ShipmentStage>';

        // Conductor (modalidad privada)
        if ($guia->guia_modalidad_traslado === '02' && $guia->guia_conductor_numero_doc) {
            $xml .= '
            <cac:DriverPerson>
                <cbc:ID schemeID="' . ($guia->guia_conductor_tipo_doc ?? '1') . '" schemeName="SUNAT:Identificador De Documento De Identidad" schemeAgencyName="PE:SUNAT" schemeURI="urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo06">' . $guia->guia_conductor_numero_doc . '</cbc:ID>
                <cbc:FirstName><![CDATA[' . $guia->guia_conductor_nombre . ']]></cbc:FirstName>
                <cbc:FamilyName>-</cbc:FamilyName>
            </cac:DriverPerson>';
            if ($guia->guia_conductor_licencia) {
                $xml .= '
            <cac:TransportMeans>
                <cac:RoadTransport>
                    <cbc:LicensePlateID>' . $guia->guia_vehiculo_placa . '</cbc:LicensePlateID>
                </cac:RoadTransport>
            </cac:TransportMeans>';
            }
        }

        $xml .= '
        </cac:ShipmentStage>
        <cac:Delivery>
            <cac:DeliveryAddress>
                <cbc:ID schemeAgencyName="PE:INEI" schemeName="Ubigeos">' . ($guia->guia_llegada_ubigeo ?? '150101') . '</cbc:ID>
                <cbc:StreetName><![CDATA[' . $guia->guia_llegada_direccion . ']]></cbc:StreetName>
            </cac:DeliveryAddress>
        </cac:Delivery>';

        // Vehículo (modalidad privada)
        if ($guia->guia_modalidad_traslado === '02' && $guia->guia_vehiculo_placa) {
            $xml .= '
        <cac:TransportHandlingUnit>
            <cac:TransportEquipment>
                <cbc:ID>' . $guia->guia_vehiculo_placa . '</cbc:ID>
            </cac:TransportEquipment>
        </cac:TransportHandlingUnit>';
        }

        $xml .= '
        <cac:OriginAddress>
            <cbc:ID schemeAgencyName="PE:INEI" schemeName="Ubigeos">' . ($guia->guia_partida_ubigeo ?? '150101') . '</cbc:ID>
            <cbc:StreetName><![CDATA[' . $guia->guia_partida_direccion . ']]></cbc:StreetName>
        </cac:OriginAddress>
    </cac:Shipment>';

        // Líneas de detalle
        foreach ($detalle as $i => $item) {
            $xml .= '
    <cac:DespatchLine>
        <cbc:ID>' . ($i + 1) . '</cbc:ID>
        <cbc:DeliveredQuantity unitCode="' . ($item->detalle_unidad_medida ?? 'NIU') . '">' . number_format($item->detalle_cantidad, 3, '.', '') . '</cbc:DeliveredQuantity>
        <cac:OrderLineReference>
            <cbc:LineID>' . ($i + 1) . '</cbc:LineID>
        </cac:OrderLineReference>
        <cac:Item>
            <cbc:Description><![CDATA[' . $item->detalle_descripcion . ']]></cbc:Description>
            <cac:SellersItemIdentification>
                <cbc:ID>' . ($item->detalle_codigo ?? $item->id_pro) . '</cbc:ID>
            </cac:SellersItemIdentification>
        </cac:Item>
    </cac:DespatchLine>';
        }

        $xml .= '
</DespatchAdvice>';

        $doc->loadXML($xml);
        $doc->save($nombrexml . '.XML');
    }



}
