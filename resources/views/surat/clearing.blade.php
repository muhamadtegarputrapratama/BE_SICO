<?php
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <style>
        @page {
            size: A4;
            margin: 18mm 20mm 20mm 20mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10.5pt;
            color: #0f172a;
            margin: 0;
            line-height: 1.45;
        }


        .kop {
            width: 100%;
            border-bottom: 1.5px solid #334155;
            padding-bottom: 10px;
            margin-bottom: 24px;
        }

        .kop-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-cell {
            width: 65px;
            vertical-align: middle;
            text-align: left;
        }

        .logo {
            width: 58px;
            height: auto;
        }

        .institution {
            width: 58%;
            vertical-align: middle;
            text-align: left;
        }

        .government {
            font-size: 7.5pt;
            font-weight: bold;
            line-height: 1.2;
            margin-bottom: 2px;
        }

        .university {
            font-size: 17pt;
            font-weight: bold;
            line-height: 1.1;
            margin-bottom: 3px;
        }

        .faculty {
            font-size: 8.5pt;
            font-weight: bold;
            line-height: 1.2;
        }

        .contact {
            width: 42%;
            vertical-align: middle;
            text-align: left;
            font-size: 7pt;
            line-height: 1.4;
            color: #334155;
            padding-left: 12px;
        }

        .contact div {
            margin-bottom: 2px;
        }


        .title {
            text-align: center;
            margin-top: 16px;
            margin-bottom: 24px;
        }

        .title h1 {
            font-size: 13pt;
            margin: 0;
            font-weight: bold;
            text-decoration: underline;
            letter-spacing: 0.2px;
        }


        .content {
            margin-bottom: 20px;
        }

        .opening {
            margin-bottom: 14px;
        }

        .identity {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .identity td {
            padding: 3px 0;
            vertical-align: top;
        }

        .identity .label {
            width: 130px;
            font-weight: normal;
        }

        .identity .separator {
            width: 15px;
        }

        .paragraph {
            margin-bottom: 20px;
            text-align: justify;
        }

        .signature {
            width: 100%;
            margin-top: 10px;
        }

        .signature-wrapper {
            width: 260px;
            margin-left: auto;
            text-align: center;
        }

        .date {
            text-align: left;
            margin-bottom: 4px;
        }

        .signature-position {
            text-align: left;
            margin-bottom: 8px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .qr-cell {
            width: 90px;
            vertical-align: bottom;
            text-align: left;
            padding-right: 8px;
        }

        .qr {
            width: 80px;
            height: 80px;
        }

        .ttd-cell {
            vertical-align: bottom;
            text-align: left;
        }

        .signature-box {
            width: 150px;
            height: 80px;
            border: 1px solid #334155;
            display: block;
        }

        .signature-image {
            max-width: 145px;
            max-height: 75px;
            margin: 2px auto;
            display: block;
        }

        .signature-name {
            text-align: left;
            font-weight: bold;
            text-decoration: underline;
            padding-top: 8px;
        }

        .nip {
            text-align: left;
            padding-top: 2px;
        }

      
        .footer {
            margin-top: 40px;
            font-size: 8pt;
            color: #64748b;
            text-align: center;
            border-top: 0.5px solid #cbd5e1;
            padding-top: 8px;
        }
    </style>
</head>

<body>
    <div class="kop">
        <table class="kop-table">
            <tr>
                <td class="logo-cell">
                    <img src="{{ public_path('images/logo-ipb.png') }}" class="logo">
                </td>

                <td class="institution">
                    <div class="government">
                        KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI
                    </div>
                    <div class="university">
                        INSTITUT PERTANIAN BOGOR
                    </div>
                    <div class="faculty">
                        FAKULTAS KEHUTANAN DAN LINGKUNGAN
                    </div>
                </td>

                <td class="contact">
                    Kampus IPB Dramaga, Bogor 16680, Indonesia
                    <br>
                    Telp. 0251-8621677
                    <br>
                    Email: kehutanan@apps.ipb.ac.id
                </td>
            </tr>
        </table>
    </div>

    <div class="title">
        <h1>SURAT KETERANGAN</h1>
    </div>

    <div class="content">
        <div class="opening">
            Yang bertanda tangan di bawah ini menerangkan bahwa:
        </div>

        <table class="identity">
            <tr>
                <td class="label">NIM</td>
                <td class="separator">:</td>
                <td>{{ $surat->nim }}</td>
            </tr>
            <tr>
                <td class="label">Nama</td>
                <td class="separator">:</td>
                <td>{{ $surat->nama }}</td>
            </tr>
            <tr>
                <td class="label">Departemen</td>
                <td class="separator">:</td>
                <td>{{ $surat->departemen }}</td>
            </tr>
        </table>

        <div class="paragraph">
            Sudah tidak lagi mempunyai pinjaman buku, majalah, uang, dll.
        </div>
    </div>

    <div class="signature">
        <div class="signature-wrapper">
            <div class="date">
                Bogor, {{ \Carbon\Carbon::parse($surat->tanggal_surat)->translatedFormat('d F Y') }}
            </div>

            <div class="signature-position">
                Kabag TU
            </div>

            <table class="signature-table">
                <tr>
                    <td class="qr-cell">
                        <img src="data:image/svg+xml;base64,{{ $qrCode }}" class="qr">
                    </td>

                    <td class="ttd-cell">
                        <div class="signature-box">
                            @if (isset($signatureImage) && $signatureImage)
                                <img src="{{ $signatureImage }}" class="signature-image">
                            @endif
                        </div>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" class="signature-name">
                        Pungki Prayughi, S.Kom, M.Kom
                    </td>
                </tr>

                <tr>
                    <td colspan="2" class="nip">
                        NIP. 197403092009101001
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        Dokumen ini diterbitkan secara elektronik melalui
        Sistem Informasi Clearing Online (SICO).
    </div>
</body>

</html>
