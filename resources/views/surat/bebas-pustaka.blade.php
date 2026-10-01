<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Bebas Pustaka</title>

    <style>
        @page {
            size: A4;
            margin: 15mm 20mm 20mm 20mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "Helvetica", Arial, sans-serif;
            font-size: 11.5pt;
            color: #000;
            margin: 0;
            line-height: 1.5;
        }

        /* ===== KOP SURAT ===== */
        .kop {
            width: 100%;
            border-bottom: 1.5px solid #000;
            padding-bottom: 8px;
            margin-bottom: 22px;
        }

        .kop-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-cell {
            width: 90px;
            vertical-align: middle;
            text-align: left;
        }

        .logo {
            width: 80px;
            height: auto;
        }

        .institution {
            vertical-align: middle;
            text-align: center;
        }

        .government {
            font-size: 11pt;
            line-height: 1.3;
        }

        .faculty {
            font-size: 11pt;
            line-height: 1.3;
        }

        .library {
            font-size: 18pt;
            font-weight: bold;
            line-height: 1.3;
            margin: 2px 0;
        }

        .address {
            font-size: 9pt;
            line-height: 1.3;
        }

        /* ===== JUDUL ===== */
        .title {
            text-align: center;
            margin-bottom: 26px;
        }

        .title .judul {
            font-size: 12pt;
            margin: 0;
        }

        .title .nomor {
            font-size: 12pt;
            margin-top: 2px;
        }

        /* ===== ISI ===== */
        .opening {
            margin-bottom: 18px;
        }

        .identity {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
        }

        .identity td {
            padding: 4px 0;
            vertical-align: top;
        }

        .identity .label {
            width: 105px;
            font-weight: bold;
        }

        .identity .separator {
            width: 15px;
        }

        .identity .value {
            border-bottom: 1px dotted #000;
        }

        .paragraph {
            margin-bottom: 16px;
            text-align: left;
        }

        /* ===== TANDA TANGAN ===== */
        .signature {
            width: 100%;
            margin-top: 30px;
        }

        .signature-wrapper {
            width: 250px;
            margin-left: 62%;
            text-align: left;
        }

        .signature-space {
            height: 90px;
            padding: 6px 0;
        }

        .qr {
            width: 80px;
            height: 80px;
        }

        .signature-image {
            max-width: 150px;
            max-height: 85px;
        }

        .signature-name {
            font-weight: normal;
        }
    </style>
</head>

<body>

    {{-- KOP --}}
    <div class="kop">
        <table class="kop-table">
            <tr>
                <td class="logo-cell">
                    <img src="{{ public_path('images/logo-ipb.png') }}" class="logo">
                </td>

                <td class="institution">
                    <div class="government">
                        KEMENTERIAN RISET, TEKNOLOGI DAN PENDIDIKAN TINGGI
                    </div>
                    <div class="faculty">
                        FAKULTAS KEHUTANAN INSTITUT PERTANIAN BOGOR
                    </div>
                    <div class="library">PERPUSTAKAAN</div>
                    <div class="address">
                        Kampus IPB Darmaga Bogor 16001. Alamat Kawat : FAHUTAN Bogor
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- JUDUL --}}
    <div class="title">
        <div class="judul">SURAT KETERANGAN BEBAS PUSTAKA</div>
        <div class="nomor">
            No. {{ $surat->nomor_surat ?? '' }}/IT3.F5/KP/{{ \Carbon\Carbon::parse($surat->tanggal_surat)->format('Y') }}
        </div>
    </div>

    {{-- ISI --}}
    <div class="opening">
        Yang bertanda tangan dibawah ini, menerangkan bahwa :
    </div>

    <table class="identity">
        <tr>
            <td class="label">Nama</td>
            <td class="separator">:</td>
            <td class="value">{{ $surat->nama }}</td>
        </tr>
        <tr>
            <td class="label">Departemen</td>
            <td class="separator">:</td>
            <td class="value">{{ $surat-> }}</td>
        </tr>
        <tr>
            <td class="label">NIM</td>
            <td class="separator">:</td>
            <td class="value">{{ $surat->nim }}</td>
        </tr>
    </table>

    <div class="paragraph">
        Tidak mempunyai pinjaman Bahan Pustaka pada Perpustakaan Fakultas Kehutanan
        Institut Pertanian Bogor.
    </div>

    <div class="paragraph">
        Demikian Surat Keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.
    </div>

    {{-- TANDA TANGAN --}}
    <div class="signature">
        <div class="signature-wrapper">
            <div>Bogor, {{ \Carbon\Carbon::parse($surat->tanggal_surat)->translatedFormat('d F Y') }}</div>
            <div>Pustakawan,</div>

            <div class="signature-space">
                <img src="data:image/svg+xml;base64,{{ $qrCode }}" class="qr">
            </div>

            <div class="signature-name">Wawan, S.E.</div>
            <div>NIP. 197305182007011001</div>
        </div>
    </div>

</body>

</html>