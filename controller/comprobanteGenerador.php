<?php
require_once(__DIR__ . "/../libs/fpdf/fpdf.php");

class ComprobanteGenerador {

    const RUC_EMISOR = "1792864573001";
    const RAZON_SOCIAL_EMISOR = "SISTEMA DE GESTION DE VENTAS S.A.";
    const NOMBRE_COMERCIAL = "GESTION DE VENTAS";
    const DIRECCION_MATRIZ = "Av. Amazonas N24-123 y Av. Colon, Quito, Ecuador";
    const OBLIGADO_CONTABILIDAD = "NO";

    public static function calcularDigitoVerificador($cadena) {
        $suma = 0;
        $factor = 2;
        $longitud = strlen($cadena);
        for ($i = $longitud - 1; $i >= 0; $i--) {
            $suma += intval($cadena[$i]) * $factor;
            $factor = ($factor == 7) ? 2 : $factor + 1;
        }
        $residuo = $suma % 11;
        $digito = 11 - $residuo;
        if ($digito == 11) {
            $digito = 0;
        } elseif ($digito == 10) {
            $digito = 1;
        }
        return $digito;
    }

    public static function generarClaveAcceso($fechaEmision, $tipoComprobante, $ruc, $ambiente, $serie, $secuencial, $codigoNumerico, $tipoEmision) {
        $fecha = str_replace("/", "", $fechaEmision);
        $fecha = str_replace("-", "", $fecha);
        $clave48 = $fecha . $tipoComprobante . $ruc . $ambiente . $serie . $secuencial . $codigoNumerico . $tipoEmision;
        $digito = self::calcularDigitoVerificador($clave48);
        return $clave48 . $digito;
    }

    public static function obtenerTipoIdentificacion($cedulaRuc) {
        $longitud = strlen(trim($cedulaRuc));
        if ($cedulaRuc === "9999999999999" || $cedulaRuc === "9999999999") {
            return "07";
        } elseif ($longitud == 10) {
            return "05";
        } elseif ($longitud == 13) {
            return "04";
        } else {
            return "06";
        }
    }

    public static function generarXML($venta) {
        $fechaEmision = date("d/m/Y", strtotime($venta['fecha']));
        $partesFactura = explode("-", $venta['numeroFactura']);
        $estab = $partesFactura[0] ?? "001";
        $ptoEmi = $partesFactura[1] ?? "001";
        $secuencial = $partesFactura[2] ?? sprintf("%09d", $venta['idVenta']);
        
        $ambiente = "1";
        $tipoEmision = "1";
        $codigoNumerico = sprintf("%08d", $venta['idVenta']);
        
        $claveAcceso = self::generarClaveAcceso(
            $fechaEmision,
            "01",
            self::RUC_EMISOR,
            $ambiente,
            $estab . $ptoEmi,
            $secuencial,
            $codigoNumerico,
            $tipoEmision
        );

        $tipoIdComprador = self::obtenerTipoIdentificacion($venta['c_cedula']);
        $nombreComprador = htmlspecialchars($venta['c_nombres'] . ' ' . $venta['c_apellidos'], ENT_XML1, 'UTF-8');
        
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><factura id="comprobante" version="1.1.0"></factura>');
        
        $infoTributaria = $xml->addChild('infoTributaria');
        $infoTributaria->addChild('ambiente', $ambiente);
        $infoTributaria->addChild('tipoEmision', $tipoEmision);
        $infoTributaria->addChild('razonSocial', htmlspecialchars(self::RAZON_SOCIAL_EMISOR, ENT_XML1, 'UTF-8'));
        $infoTributaria->addChild('nombreComercial', htmlspecialchars(self::NOMBRE_COMERCIAL, ENT_XML1, 'UTF-8'));
        $infoTributaria->addChild('ruc', self::RUC_EMISOR);
        $infoTributaria->addChild('claveAcceso', $claveAcceso);
        $infoTributaria->addChild('codDoc', '01');
        $infoTributaria->addChild('estab', $estab);
        $infoTributaria->addChild('ptoEmi', $ptoEmi);
        $infoTributaria->addChild('secuencial', $secuencial);
        $infoTributaria->addChild('dirMatriz', htmlspecialchars(self::DIRECCION_MATRIZ, ENT_XML1, 'UTF-8'));

        $infoFactura = $xml->addChild('infoFactura');
        $infoFactura->addChild('fechaEmision', $fechaEmision);
        $infoFactura->addChild('dirEstablecimiento', htmlspecialchars(self::DIRECCION_MATRIZ, ENT_XML1, 'UTF-8'));
        $infoFactura->addChild('obligadoContabilidad', self::OBLIGADO_CONTABILIDAD);
        $infoFactura->addChild('tipoIdentificacionComprador', $tipoIdComprador);
        $infoFactura->addChild('razonSocialComprador', $nombreComprador);
        $infoFactura->addChild('identificacionComprador', $venta['c_cedula']);
        $infoFactura->addChild('totalSinImpuestos', number_format($venta['subtotal'], 2, '.', ''));
        $infoFactura->addChild('totalDescuento', '0.00');

        $totalConImpuestos = $infoFactura->addChild('totalConImpuestos');
        
        $tarifaIva = 12;
        $codigoPorcentaje = 2;
        
        if (!empty($venta['detalles'])) {
            $detalleEjemplo = $venta['detalles'][0];
            if ($detalleEjemplo['precio'] > 0) {
                $calcTarifa = round(($detalleEjemplo['iva'] / ($detalleEjemplo['cantidad'] * $detalleEjemplo['precio'])) * 100);
                if ($calcTarifa > 0) {
                    $tarifaIva = $calcTarifa;
                    if ($tarifaIva == 15) {
                        $codigoPorcentaje = 4;
                    }
                } else {
                    $tarifaIva = 0;
                    $codigoPorcentaje = 0;
                }
            }
        }

        $totalImpuesto = $totalConImpuestos->addChild('totalImpuesto');
        $totalImpuesto->addChild('codigo', '2');
        $totalImpuesto->addChild('codigoPorcentaje', $codigoPorcentaje);
        $totalImpuesto->addChild('baseImponible', number_format($venta['subtotal'], 2, '.', ''));
        $totalImpuesto->addChild('tarifa', number_format($tarifaIva, 2, '.', ''));
        $totalImpuesto->addChild('valor', number_format($venta['iva'], 2, '.', ''));

        $infoFactura->addChild('propina', '0.00');
        $infoFactura->addChild('importeTotal', number_format($venta['total'], 2, '.', ''));
        $infoFactura->addChild('moneda', 'DOLAR');

        $pagos = $infoFactura->addChild('pagos');
        $pago = $pagos->addChild('pago');
        $pago->addChild('formaPago', '01');
        $pago->addChild('total', number_format($venta['total'], 2, '.', ''));

        $detalles = $xml->addChild('detalles');
        foreach ($venta['detalles'] as $d) {
            $detalle = $detalles->addChild('detalle');
            $detalle->addChild('codigoPrincipal', htmlspecialchars($d['producto_codigo'], ENT_XML1, 'UTF-8'));
            $detalle->addChild('descripcion', htmlspecialchars($d['producto_nombre'], ENT_XML1, 'UTF-8'));
            $detalle->addChild('cantidad', number_format($d['cantidad'], 2, '.', ''));
            $detalle->addChild('precioUnitario', number_format($d['precio'], 4, '.', ''));
            $detalle->addChild('descuento', '0.00');
            $subtotalDetalle = $d['cantidad'] * $d['precio'];
            $detalle->addChild('precioTotalSinImpuesto', number_format($subtotalDetalle, 2, '.', ''));

            $impuestos = $detalle->addChild('impuestos');
            $impuesto = $impuestos->addChild('impuesto');
            $impuesto->addChild('codigo', '2');
            
            $itemTarifa = 0;
            $itemCodPorc = 0;
            if ($subtotalDetalle > 0) {
                $itemTarifa = round(($d['iva'] / $subtotalDetalle) * 100);
                if ($itemTarifa == 15) {
                    $itemCodPorc = 4;
                } elseif ($itemTarifa > 0) {
                    $itemCodPorc = 2;
                }
            }
            
            $impuesto->addChild('codigoPorcentaje', $itemCodPorc);
            $impuesto->addChild('tarifa', number_format($itemTarifa, 2, '.', ''));
            $impuesto->addChild('baseImponible', number_format($subtotalDetalle, 2, '.', ''));
            $impuesto->addChild('valor', number_format($d['iva'], 2, '.', ''));
        }

        $infoAdicional = $xml->addChild('infoAdicional');
        if (!empty($venta['c_direccion'])) {
            $campoDir = $infoAdicional->addChild('campoAdicional', htmlspecialchars($venta['c_direccion'], ENT_XML1, 'UTF-8'));
            $campoDir->addAttribute('nombre', 'Direccion');
        }
        if (!empty($venta['c_telefono'])) {
            $campoTel = $infoAdicional->addChild('campoAdicional', htmlspecialchars($venta['c_telefono'], ENT_XML1, 'UTF-8'));
            $campoTel->addAttribute('nombre', 'Telefono');
        }
        if (!empty($venta['c_correo'])) {
            $campoMail = $infoAdicional->addChild('campoAdicional', htmlspecialchars($venta['c_correo'], ENT_XML1, 'UTF-8'));
            $campoMail->addAttribute('nombre', 'Email');
        }

        return $xml->asXML();
    }

    public static function generarPDF($venta) {
        $fechaEmision = date("d/m/Y", strtotime($venta['fecha']));
        $partesFactura = explode("-", $venta['numeroFactura']);
        $estab = $partesFactura[0] ?? "001";
        $ptoEmi = $partesFactura[1] ?? "001";
        $secuencial = $partesFactura[2] ?? sprintf("%09d", $venta['idVenta']);
        
        $ambiente = "1";
        $tipoEmision = "1";
        $codigoNumerico = sprintf("%08d", $venta['idVenta']);
        
        $claveAcceso = self::generarClaveAcceso(
            $fechaEmision,
            "01",
            self::RUC_EMISOR,
            $ambiente,
            $estab . $ptoEmi,
            $secuencial,
            $codigoNumerico,
            $tipoEmision
        );

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        
        $pdf->SetDrawColor(120, 120, 120);
        $pdf->SetLineWidth(0.2);

        $pdf->Rect(10, 10, 92, 54, 'D');

        $pdf->SetXY(12, 12);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(230, 126, 34);
        $pdf->Cell(88, 5, utf8_decode(self::NOMBRE_COMERCIAL), 0, 1, 'L');
        
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetX(12);
        $pdf->Cell(88, 4, utf8_decode(self::RAZON_SOCIAL_EMISOR), 0, 1, 'L');

        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        
        $pdf->SetX(12);
        $pdf->Cell(25, 4, utf8_decode("Dirección matriz:"), 0, 0, 'L');
        $pdf->SetX(37);
        $pdf->MultiCell(63, 4, utf8_decode(self::DIRECCION_MATRIZ), 0, 'L');

        $pdf->SetX(12);
        $pdf->Cell(25, 4, utf8_decode("Dirección sucursal:"), 0, 0, 'L');
        $pdf->SetX(37);
        $pdf->MultiCell(63, 4, utf8_decode(self::DIRECCION_MATRIZ), 0, 'L');
        
        $pdf->SetX(12);
        $pdf->Cell(88, 4, "Obligado a llevar contabilidad: " . self::OBLIGADO_CONTABILIDAD, 0, 1, 'L');

        $pdf->Rect(106, 10, 94, 54, 'D');
        
        $pdf->SetXY(108, 12);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(90, 5, "R.U.C.:  " . self::RUC_EMISOR, 0, 1, 'L');
        
        $pdf->SetXY(106, 18);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFillColor(27, 79, 114);
        $pdf->Cell(94, 6, "FACTURA", 0, 1, 'C', true);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(108, 25);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(90, 5, "No. " . $venta['numeroFactura'], 0, 1, 'L');

        $pdf->SetX(108);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(90, 4, utf8_decode("NÚMERO DE AUTORIZACIÓN / CLAVE DE ACCESO:"), 0, 1, 'L');
        
        $pdf->SetX(108);
        $pdf->SetFont('Arial', '', 7.5);
        $pdf->Cell(90, 4, $claveAcceso, 0, 1, 'L');

        $pdf->SetX(108);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(90, 4, utf8_decode("FECHA Y HORA DE AUTORIZACIÓN:"), 0, 1, 'L');
        
        $pdf->SetX(108);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(90, 4, $fechaEmision . " " . date("H:i:s", strtotime($venta['fecha'])), 0, 1, 'L');

        $pdf->SetX(108);
        $pdf->Cell(90, 4, "AMBIENTE: PRUEBAS       EMISION: NORMAL", 0, 1, 'L');

        $pdf->SetY(68);
        
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(130, 6, utf8_decode("Sr(es):   " . $venta['c_nombres'] . ' ' . $venta['c_apellidos']), 1, 0, 'L');
        $pdf->Cell(60, 6, utf8_decode("R.U.C./C.I.:   " . $venta['c_cedula']), 1, 1, 'L');
        
        $pdf->Cell(130, 6, utf8_decode("FECHA EMISIÓN:   " . $fechaEmision), 1, 0, 'L');
        $pdf->Cell(60, 6, utf8_decode("GUÍA DE REMISIÓN:   S/N"), 1, 1, 'L');

        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(230, 126, 34);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(20, 7, "CANT.", 1, 0, 'C', true);
        $pdf->Cell(110, 7, utf8_decode("DESCRIPCIÓN"), 1, 0, 'C', true);
        $pdf->Cell(30, 7, "P. UNITARIO", 1, 0, 'C', true);
        $pdf->Cell(30, 7, "V. TOTAL", 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        foreach ($venta['detalles'] as $d) {
            $subtotalDetalle = $d['cantidad'] * $d['precio'];
            
            $xBefore = $pdf->GetX();
            $yBefore = $pdf->GetY();
            
            $pdf->Cell(20, 6, number_format($d['cantidad'], 2), 'LBR', 0, 'C');
            
            $pdf->Cell(110, 6, utf8_decode($d['producto_nombre']), 'BR', 0, 'L');
            
            $pdf->Cell(30, 6, number_format($d['precio'], 4), 'BR', 0, 'R');
            $pdf->Cell(30, 6, number_format($subtotalDetalle, 2), 'BR', 1, 'R');
        }

        $pdf->Ln(5);
        $yTotals = $pdf->GetY();

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(230, 126, 34);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(55, 5, "FORMA DE PAGO", 1, 0, 'C', true);
        $pdf->Cell(25, 5, "VALOR", 1, 1, 'C', true);
        
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(55, 5, utf8_decode("Sin utilización del sistema financiero"), 1, 0, 'L');
        $pdf->Cell(25, 5, number_format($venta['total'], 2), 1, 1, 'R');

        $pdf->SetXY(110, $yTotals);

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(60, 5, "SUBTOTAL 12%", 1, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(30, 5, number_format($venta['subtotal'], 2), 1, 1, 'R');

        $pdf->SetX(110);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(60, 5, "SUBTOTAL 0%", 1, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(30, 5, "0.00", 1, 1, 'R');

        $pdf->SetX(110);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(60, 5, "DESCUENTO", 1, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(30, 5, "0.00", 1, 1, 'R');

        $pdf->SetX(110);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(60, 5, "SUBTOTAL SIN IMPUESTOS", 1, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(30, 5, number_format($venta['subtotal'], 2), 1, 1, 'R');

        $pdf->SetX(110);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(60, 5, "VALOR IVA 12%", 1, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(30, 5, number_format($venta['iva'], 2), 1, 1, 'R');

        $pdf->SetX(110);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(60, 6, "VALOR TOTAL", 1, 0, 'L', true);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(30, 6, number_format($venta['total'], 2), 1, 1, 'R', true);

        $pdf->Ln(15);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(190, 5, utf8_decode("DOCUMENTO PARA USO EDUCATIVO (SIN VALIDEZ COMERCIAL) - SRI"), 0, 1, 'C');

        $pdf->Output('I', 'Factura_' . $venta['numeroFactura'] . '.pdf');
    }
}
?>
