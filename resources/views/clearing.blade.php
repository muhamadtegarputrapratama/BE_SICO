<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
           size: A4;
           margin: 18mm 23mm 20mm 23mm;
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
           border-bottom: 1.4px solid #c7d2fe;
           padding-bottom: 11px;
           margin-bottom: 24px;
        }

        .kop-table {
           width: 100%;
           border-collapse: collapse;
        }

        .logo-cell {
           width: 78px;
           vertical-align: middle;
           text-align: left;
        }

        .logo {
           width: 64px;
           height: auto;
        }

        .institution {
           vertical-align: middle;
           text-align: left;
        }

        .institution .university {
           font-size: 14.5pt;
           font-weight: bold;
           line-height: 1.2;
           margin-bottom: 3px;
        }

        .institution .faculty {
           font-size: 10.5pt;
           font-weight: bold;
           line-height: 1.2;
           margin-bottom: 4px;
        }

        .institution .address {
           font-size: 7.5pt;
           line-height: 1.35;
           color: #475569;
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

        .title p {
           margin: 4px 0 0;
           font-size: 9.5pt;
           color: #334155;
        }

        .content {
           text-align: justify;
        }

        .opening {
           margin-bottom: 14px;
        }

        .identity {
           width: 100%;
           margin-bottom: 16px;
           margin-left: 14px;
        }

        .identity td {
           padding: 1.5px 0;
           vertical-align: top;
        }

        .identity .label {
           width: 95px;
        }

        .identity .separator {
           width: 15px;
        }

        .paragraph {
           margin-top: 12px;
           margin-bottom: 12px;
        }

        .closing {
           margin-top: 18px;
           text-align: justify;
        }

        .signature {
           width: 100%;
           margin-top: 30px;
        }

        .signature-table {
           width: 100%;
           border-collapse: collapse;
        }

        .signature-left {
           width: 48%;
           vertical-align: top;
           text-align: center;
        }

        .signature-right {
           width: 52%;
           vertical-align: top;
           text-align: center;
        }

        .date {
           margin-bottom: 6px;
           color: #334155;
        }

        .qr {
           width: 100px;
           height: 100px;
           margin-top: 3px;
        }

        .verification {
           font-size: 7pt;
           color: #64748b;
           margin-top: 2px;
        }

        .signature-space {
           height: 78px;
        }

        .signature-name {
           margin-top: 4px;
           font-weight: bold;
           text-decoration: underline;
           font-size: 9.5pt;
        }

        .signature-position {
           font-size: 8.5pt;
           margin-top: 2px;
           color: #475569;
        }

        .nip {
           font-size: 8pt;
           margin-top: 2px;
           color: #475569;
        }

        .footer {
           position: fixed;
           bottom: -5mm;
           left: 0;
           right: 0;
           text-align: center;
           font-size: 6.5pt;
           color: #64748b;
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
                   <div class="university">INSTITUT PERTANIAN BOGOR</div>
                   <div class="faculty">FAKULTAS KEHUTANAN DAN LINGKUNGAN</div>
                   <div class="address">
                       Kampus IPB Dramaga, Bogor 16680, Indonesia<br>
                       Telp. 0251-8621677 &nbsp;&nbsp;|&nbsp;&nbsp; Email: kehutanan@apps.ipb.ac.id
                   </div>
               </td>
           </tr>
        </table>
    </div>

    <div class="title">
        <h1>SURAT KETERANGAN</h1>
        <p>Nomor: {{ $surat->nomor_surat }}</p>
    </div>

    <div class="content">
        <div class="opening">Yang bertanda tangan di bawah ini menerangkan bahwa:</div>

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
           <tr>
               <td class="label">Program Studi</td>
               <td class="separator">:</td>
               <td>{{ $surat->program_studi }}</td>
           </tr>
        </table>

        <div class="paragraph">Yang bersangkutan benar-benar merupakan mahasiswa Institut Pertanian Bogor.</div>
        <div class="paragraph">Surat keterangan ini diberikan untuk keperluan:</div>
        <div class="paragraph"><strong>{{ $surat->keperluan ?? 'Keperluan administrasi akademik.' }}</strong></div>
        <div class="closing">Demikian surat keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</div>
    </div>

    <table class="signature-table signature">
        <tr>
           <td class="signature-left">
               <div>Mengetahui,</div>
               <div class="signature-space"></div>
               <div class="signature-name">................................</div>
               <div class="signature-position">Pejabat yang Berwenang</div>
           </td>
           <td class="signature-right">
               <div class="date">Bogor, {{ \Carbon\Carbon::parse($surat->tanggal_surat)->translatedFormat('d F Y') }}</div>
               <img src="data:image/svg+xml;base64,{{ $qrCode }}" class="qr">
               <div class="verification">Scan QR Code untuk verifikasi</div>
               <div style="height: 17px;"></div>
               <div class="signature-name">{{ $signatureLabel }}</div>
               <div class="nip">Pejabat yang Berwenang</div>
           </td>
        </tr>
    </table>

    <div class="footer">Dokumen ini diterbitkan secara elektronik melalui Sistem Informasi Clearing Online (SICO).</div>
</body>
</html>
