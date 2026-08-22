import React, { useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { Container } from "reactstrap";

/**
 * Kalkulator zakat untuk pengunjung yang belum punya akun.
 *
 * Hitungannya berjalan di browser memakai parameter fikih yang umum dipakai di
 * Indonesia, jadi siapa pun bisa memakainya tanpa login. Angka resmi tetap
 * berasal dari perhitungan amil di dalam aplikasi (modul 05), karena nisab dan
 * kaidah tiap lembaga dapat berbeda.
 */

const NISAB_EMAS_GRAM = 85;
const NISAB_PERAK_GRAM = 595;
const KADAR_ZAKAT = 0.025;
const BERAS_PER_JIWA_KG = 2.5;

type FieldKind = "uang" | "angka";

interface Field {
  key: string;
  label: string;
  hint?: string;
  kind: FieldKind;
  optional?: boolean;
  kurang?: boolean;
}

interface Pesan {
  nada: "baik" | "perhatian" | "info";
  teks: string;
}

interface Hasil {
  wajib: boolean;
  /** Sudah melewati batas nisab. Kalau belum, tidak ada kewajiban sama sekali. */
  cukupNisab: boolean;
  dasar: number;
  nisab: number | null;
  zakat: number;
  rincian: [string, string][];
  pesan: Pesan[];
}

interface JenisZakat {
  kode: string;
  nama: string;
  ringkas: string;
  penjelasan: string;
  syarat: string[];
  fields: Field[];
  butuhHargaEmas: boolean;
  butuhHaul: boolean;
  hitung: (nilai: Record<string, number>, hargaEmas: number, haul: boolean) => Hasil;
}

const rupiah = (value: number) =>
  new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(
    Math.max(0, Math.round(value)),
  );

const angka = (value: number) => new Intl.NumberFormat("id-ID", { maximumFractionDigits: 2 }).format(value);

const parse = (value: string) => {
  const bersih = Number(value.replace(/[^\d]/g, ""));
  return Number.isFinite(bersih) ? bersih : 0;
};

/** Pesan yang sama dipakai beberapa jenis zakat, jadi dikumpulkan di sini. */
const pesanNisab = (dasar: number, nisab: number): Pesan[] => {
  if (dasar >= nisab) {
    return [
      {
        nada: "baik",
        teks: `Harta yang dihitung ${rupiah(dasar)}, sudah melewati batas nisab ${rupiah(nisab)}.`,
      },
    ];
  }

  return [
    {
      nada: "perhatian",
      teks:
        `Harta yang dihitung ${rupiah(dasar)}, sedangkan batas nisab ${rupiah(nisab)}. ` +
        `Masih kurang ${rupiah(nisab - dasar)}, jadi belum wajib zakat.`,
    },
    {
      nada: "info",
      teks: "Belum wajib zakat bukan berarti tidak boleh berbagi. Infak dan sedekah tetap dianjurkan dan nilainya bebas.",
    },
  ];
};

const pesanHaul = (haul: boolean): Pesan[] =>
  haul
    ? []
    : [
        {
          nada: "perhatian",
          teks:
            "Haul belum terpenuhi. Zakat jenis ini baru wajib setelah harta bertahan di atas nisab selama satu tahun hijriah. " +
            "Angka di bawah adalah perkiraan bila nanti haulnya genap.",
        },
      ];

const JENIS: JenisZakat[] = [
  {
    kode: "penghasilan",
    nama: "Zakat Penghasilan",
    ringkas: "Gaji, honor, dan pendapatan rutin",
    penjelasan:
      "Zakat dari pemasukan rutin seperti gaji bulanan, honor, atau hasil profesi. Boleh ditunaikan tiap bulan supaya lebih ringan, " +
      "asalkan penghasilan setahun sudah melewati batas nisab.",
    syarat: [
      "Batas nisabnya setara 85 gram emas dalam setahun. Untuk hitungan bulanan, nisab dibagi dua belas.",
      "Yang dihitung adalah penghasilan bersih setelah dikurangi kebutuhan pokok dan cicilan utang yang jatuh tempo.",
      "Kadarnya 2,5 persen.",
    ],
    fields: [
      { key: "gaji", label: "Penghasilan per bulan", hint: "Gaji pokok dan tunjangan rutin.", kind: "uang" },
      { key: "lain", label: "Penghasilan lain per bulan", hint: "Honor, bonus, atau usaha sampingan.", kind: "uang", optional: true },
      {
        key: "kebutuhan",
        label: "Kebutuhan pokok dan cicilan per bulan",
        hint: "Boleh dikosongkan bila ingin menghitung dari penghasilan kotor.",
        kind: "uang",
        optional: true,
        kurang: true,
      },
    ],
    butuhHargaEmas: true,
    butuhHaul: false,
    hitung: (nilai, hargaEmas) => {
      const bruto = nilai.gaji + nilai.lain;
      const dasar = Math.max(0, bruto - nilai.kebutuhan);
      const nisabBulanan = (NISAB_EMAS_GRAM * hargaEmas) / 12;

      const pesan: Pesan[] = dasar === 0 && bruto > 0
        ? [
            {
              nada: "perhatian",
              teks: "Setelah dikurangi kebutuhan pokok dan cicilan, tidak ada penghasilan tersisa yang perlu dizakati bulan ini.",
            },
          ]
        : pesanNisab(dasar, nisabBulanan);

      return {
        wajib: dasar >= nisabBulanan && dasar > 0,
        cukupNisab: dasar >= nisabBulanan && dasar > 0,
        dasar,
        nisab: nisabBulanan,
        zakat: dasar * KADAR_ZAKAT,
        rincian: [
          ["Penghasilan sebulan", rupiah(bruto)],
          ["Dikurangi kebutuhan pokok dan cicilan", rupiah(nilai.kebutuhan)],
          ["Penghasilan bersih", rupiah(dasar)],
          ["Nisab sebulan (85 gram emas dibagi 12)", rupiah(nisabBulanan)],
        ],
        pesan,
      };
    },
  },
  {
    kode: "maal",
    nama: "Zakat Maal",
    ringkas: "Tabungan, emas, dan investasi",
    penjelasan:
      "Zakat atas harta simpanan yang mengendap: uang di tabungan, emas, saham, atau piutang yang masih bisa ditagih. " +
      "Dihitung sekali setahun, saat harta sudah genap satu tahun berada di atas nisab.",
    syarat: [
      "Batas nisabnya setara 85 gram emas.",
      "Harta harus sudah dimiliki selama satu tahun hijriah penuh, disebut haul.",
      "Utang yang jatuh tempo boleh dikurangkan lebih dulu.",
      "Kadarnya 2,5 persen.",
    ],
    fields: [
      { key: "tabungan", label: "Uang tunai dan tabungan", kind: "uang" },
      { key: "emas", label: "Nilai emas dan perak", hint: "Perkiraan harga jualnya hari ini.", kind: "uang", optional: true },
      { key: "investasi", label: "Investasi dan surat berharga", kind: "uang", optional: true },
      { key: "piutang", label: "Piutang yang masih bisa ditagih", kind: "uang", optional: true },
      { key: "utang", label: "Utang yang jatuh tempo", kind: "uang", optional: true, kurang: true },
    ],
    butuhHargaEmas: true,
    butuhHaul: true,
    hitung: (nilai, hargaEmas, haul) => {
      const bruto = nilai.tabungan + nilai.emas + nilai.investasi + nilai.piutang;
      const dasar = Math.max(0, bruto - nilai.utang);
      const nisab = NISAB_EMAS_GRAM * hargaEmas;

      return {
        wajib: dasar >= nisab && haul && dasar > 0,
        cukupNisab: dasar >= nisab && dasar > 0,
        dasar,
        nisab,
        zakat: dasar * KADAR_ZAKAT,
        rincian: [
          ["Total harta", rupiah(bruto)],
          ["Dikurangi utang jatuh tempo", rupiah(nilai.utang)],
          ["Harta bersih", rupiah(dasar)],
          [`Nisab (${NISAB_EMAS_GRAM} gram emas)`, rupiah(nisab)],
        ],
        pesan: [...pesanNisab(dasar, nisab), ...pesanHaul(haul)],
      };
    },
  },
  {
    kode: "perdagangan",
    nama: "Zakat Perdagangan",
    ringkas: "Usaha, toko, dan barang dagang",
    penjelasan:
      "Zakat untuk pelaku usaha. Yang dihitung bukan keuntungan saja, melainkan seluruh modal yang berputar: " +
      "barang dagang, kas usaha, dan piutang lancar, dikurangi utang usaha yang jatuh tempo.",
    syarat: [
      "Batas nisabnya setara 85 gram emas.",
      "Usaha sudah berjalan satu tahun hijriah penuh.",
      "Barang dagang dinilai dengan harga jual saat zakat dihitung, bukan harga beli.",
      "Kadarnya 2,5 persen.",
    ],
    fields: [
      { key: "barang", label: "Nilai barang dagang", hint: "Pakai harga jual saat ini.", kind: "uang" },
      { key: "kas", label: "Uang kas dan rekening usaha", kind: "uang", optional: true },
      { key: "piutang", label: "Piutang lancar", kind: "uang", optional: true },
      { key: "utang", label: "Utang usaha yang jatuh tempo", kind: "uang", optional: true, kurang: true },
    ],
    butuhHargaEmas: true,
    butuhHaul: true,
    hitung: (nilai, hargaEmas, haul) => {
      const bruto = nilai.barang + nilai.kas + nilai.piutang;
      const dasar = Math.max(0, bruto - nilai.utang);
      const nisab = NISAB_EMAS_GRAM * hargaEmas;

      return {
        wajib: dasar >= nisab && haul && dasar > 0,
        cukupNisab: dasar >= nisab && dasar > 0,
        dasar,
        nisab,
        zakat: dasar * KADAR_ZAKAT,
        rincian: [
          ["Modal yang berputar", rupiah(bruto)],
          ["Dikurangi utang usaha jatuh tempo", rupiah(nilai.utang)],
          ["Harta usaha bersih", rupiah(dasar)],
          [`Nisab (${NISAB_EMAS_GRAM} gram emas)`, rupiah(nisab)],
        ],
        pesan: [...pesanNisab(dasar, nisab), ...pesanHaul(haul)],
      };
    },
  },
  {
    kode: "emas",
    nama: "Zakat Emas",
    ringkas: "Emas yang disimpan",
    penjelasan:
      "Zakat atas emas yang disimpan sebagai harta. Emas yang benar-benar dipakai sehari-hari dalam batas wajar " +
      "umumnya tidak dizakati, jadi masukkan berat emas simpanan saja.",
    syarat: [
      `Batas nisabnya ${NISAB_EMAS_GRAM} gram emas. Untuk perak, nisabnya ${NISAB_PERAK_GRAM} gram.`,
      "Emas sudah disimpan selama satu tahun hijriah penuh.",
      "Kadarnya 2,5 persen, boleh dibayar dengan nilai uangnya.",
    ],
    fields: [{ key: "gram", label: "Berat emas simpanan (gram)", kind: "angka" }],
    butuhHargaEmas: true,
    butuhHaul: true,
    hitung: (nilai, hargaEmas, haul) => {
      const dasar = nilai.gram * hargaEmas;
      const nisab = NISAB_EMAS_GRAM * hargaEmas;
      const pesan: Pesan[] =
        nilai.gram >= NISAB_EMAS_GRAM
          ? [{ nada: "baik", teks: `Emas ${angka(nilai.gram)} gram sudah melewati nisab ${NISAB_EMAS_GRAM} gram.` }]
          : [
              {
                nada: "perhatian",
                teks:
                  `Emas ${angka(nilai.gram)} gram masih di bawah nisab ${NISAB_EMAS_GRAM} gram. ` +
                  `Kurang ${angka(NISAB_EMAS_GRAM - nilai.gram)} gram, jadi belum wajib zakat.`,
              },
              {
                nada: "info",
                teks: "Bila emas ini digabung dengan tabungan dan harta lain, coba hitung memakai Zakat Maal. Bisa jadi totalnya sudah mencapai nisab.",
              },
            ];

      return {
        wajib: nilai.gram >= NISAB_EMAS_GRAM && haul,
        cukupNisab: nilai.gram >= NISAB_EMAS_GRAM,
        dasar,
        nisab,
        zakat: dasar * KADAR_ZAKAT,
        rincian: [
          ["Berat emas", `${angka(nilai.gram)} gram`],
          ["Nilai emas", rupiah(dasar)],
          [`Nisab (${NISAB_EMAS_GRAM} gram)`, rupiah(nisab)],
          ["Zakat dalam bentuk emas", `${angka(nilai.gram * KADAR_ZAKAT)} gram`],
        ],
        pesan: [...pesan, ...pesanHaul(haul)],
      };
    },
  },
  {
    kode: "fitrah",
    nama: "Zakat Fitrah",
    ringkas: "Menjelang Idulfitri",
    penjelasan:
      "Zakat yang ditunaikan setiap jiwa menjelang Idulfitri, termasuk bayi yang lahir sebelum matahari terbenam di akhir Ramadan. " +
      "Besarnya 2,5 kilogram makanan pokok per orang, boleh diganti uang senilai harga berasnya.",
    syarat: [
      "Tidak ada nisab dan tidak menunggu haul. Siapa pun yang mampu menunaikannya wajib membayar.",
      "Ditunaikan untuk diri sendiri dan orang yang ditanggung nafkahnya.",
      "Paling baik ditunaikan sebelum salat Id.",
    ],
    fields: [
      { key: "jiwa", label: "Jumlah jiwa yang ditanggung", hint: "Termasuk diri sendiri.", kind: "angka" },
      { key: "harga", label: "Harga beras per kilogram", hint: "Pakai beras yang biasa Anda konsumsi.", kind: "uang" },
    ],
    butuhHargaEmas: false,
    butuhHaul: false,
    hitung: (nilai) => {
      const beras = nilai.jiwa * BERAS_PER_JIWA_KG;
      const zakat = beras * nilai.harga;

      return {
        wajib: nilai.jiwa >= 1,
        cukupNisab: nilai.jiwa >= 1,
        dasar: zakat,
        nisab: null,
        zakat,
        rincian: [
          ["Jumlah jiwa", `${angka(nilai.jiwa)} orang`],
          ["Beras per jiwa", `${BERAS_PER_JIWA_KG} kg`],
          ["Total beras", `${angka(beras)} kg`],
          ["Harga beras per kg", rupiah(nilai.harga)],
        ],
        pesan:
          nilai.jiwa >= 1
            ? [
                {
                  nada: "baik",
                  teks: `Zakat fitrah untuk ${angka(nilai.jiwa)} jiwa setara ${angka(beras)} kilogram beras.`,
                },
                {
                  nada: "info",
                  teks: "Boleh ditunaikan dalam bentuk beras langsung. Nilai uang di samping hanya penggantinya.",
                },
              ]
            : [{ nada: "perhatian", teks: "Isi jumlah jiwa minimal satu orang, yaitu diri Anda sendiri." }],
      };
    },
  },
];

const HARGA_EMAS_AWAL = "2400000";

const ZakatCalculator = () => {
  const [kode, setKode] = useState<string | null>(null);
  const [nilai, setNilai] = useState<Record<string, string>>({});
  const [hargaEmas, setHargaEmas] = useState(HARGA_EMAS_AWAL);
  const [haul, setHaul] = useState(true);

  const jenis = JENIS.find((item) => item.kode === kode) ?? null;

  const pilih = (item: JenisZakat) => {
    setKode(item.kode);
    setNilai({});
    setHaul(true);
  };

  const hasil = useMemo<Hasil | null>(() => {
    if (jenis === null) return null;

    const wajibIsi = jenis.fields.filter((field) => !field.optional);
    const belumIsi = wajibIsi.filter((field) => parse(nilai[field.key] ?? "") <= 0);

    if (belumIsi.length > 0) return null;
    if (jenis.butuhHargaEmas && parse(hargaEmas) <= 0) return null;

    const angkaInput = Object.fromEntries(
      jenis.fields.map((field) => [field.key, parse(nilai[field.key] ?? "")]),
    );

    return jenis.hitung(angkaInput, parse(hargaEmas), haul);
  }, [jenis, nilai, hargaEmas, haul]);

  const belumLengkap =
    jenis !== null &&
    hasil === null &&
    jenis.fields.filter((field) => !field.optional).map((field) => field.label);

  return (
    <section id="kalkulator" className="landing-section landing-calculator">
      <Container>
        <div className="landing-section-heading">
          <p className="landing-eyebrow text-success">HITUNG SENDIRI</p>
          <h2>Berapa zakat saya?</h2>
          <p>
            Pilih jenis zakatnya, isi angkanya, dan hasilnya muncul langsung. Kalau ternyata belum memenuhi syarat,
            kami jelaskan kenapa dan berapa kurangnya.
          </p>
        </div>

        <div className="landing-calc-types">
          {JENIS.map((item) => (
            <button
              type="button"
              key={item.kode}
              className={`landing-calc-type${kode === item.kode ? " is-active" : ""}`}
              onClick={() => pilih(item)}
              aria-pressed={kode === item.kode}
            >
              <span className="landing-calc-type-name">{item.nama}</span>
              <span className="landing-calc-type-hint">{item.ringkas}</span>
            </button>
          ))}
        </div>

        {jenis === null ? (
          <p className="landing-calc-empty">Pilih salah satu jenis zakat di atas untuk mulai menghitung.</p>
        ) : (
          <div className="row g-4 landing-calc-body">
            <div className="col-lg-7">
              <div className="landing-calc-card">
                <h3>{jenis.nama}</h3>
                <p className="landing-calc-intro">{jenis.penjelasan}</p>

                <div className="landing-calc-fields">
                  {jenis.fields.map((field) => (
                    <label key={field.key} className="landing-calc-field">
                      <span className="landing-calc-label">
                        {field.label}
                        {field.optional ? <em> (boleh dikosongkan)</em> : null}
                        {field.kurang ? <em> (akan dikurangkan)</em> : null}
                      </span>
                      <div className="landing-calc-input">
                        {field.kind === "uang" ? <span>Rp</span> : null}
                        <input
                          inputMode="numeric"
                          placeholder="0"
                          value={
                            nilai[field.key]
                              ? new Intl.NumberFormat("id-ID").format(parse(nilai[field.key]))
                              : ""
                          }
                          onChange={(event) => setNilai({ ...nilai, [field.key]: event.target.value })}
                        />
                      </div>
                      {field.hint ? <span className="landing-calc-hint">{field.hint}</span> : null}
                    </label>
                  ))}

                  {jenis.butuhHargaEmas ? (
                    <label className="landing-calc-field">
                      <span className="landing-calc-label">Harga emas per gram hari ini</span>
                      <div className="landing-calc-input">
                        <span>Rp</span>
                        <input
                          inputMode="numeric"
                          value={new Intl.NumberFormat("id-ID").format(parse(hargaEmas))}
                          onChange={(event) => setHargaEmas(event.target.value)}
                        />
                      </div>
                      <span className="landing-calc-hint">
                        Dipakai untuk menghitung batas nisab. Perbarui sesuai harga emas hari ini agar hasilnya akurat.
                      </span>
                    </label>
                  ) : null}

                  {jenis.butuhHaul ? (
                    <label className="landing-calc-check">
                      <input type="checkbox" checked={haul} onChange={(event) => setHaul(event.target.checked)} />
                      <span>
                        Harta ini sudah saya miliki selama satu tahun penuh
                        <em>Kalau belum, hasil di samping tetap tampil sebagai perkiraan.</em>
                      </span>
                    </label>
                  ) : null}
                </div>

                <div className="landing-calc-terms">
                  <h4>Syarat singkatnya</h4>
                  <ul>
                    {jenis.syarat.map((syarat) => (
                      <li key={syarat}>{syarat}</li>
                    ))}
                  </ul>
                </div>
              </div>
            </div>

            <div className="col-lg-5">
              <aside className="landing-calc-result" aria-live="polite">
                <p className="landing-eyebrow">HASIL PERHITUNGAN</p>

                {hasil === null ? (
                  <p className="landing-calc-waiting">
                    Isi dulu {Array.isArray(belumLengkap) ? belumLengkap.join(" dan ").toLowerCase() : "datanya"}
                    {jenis.butuhHargaEmas && parse(hargaEmas) <= 0 ? ", serta harga emas per gram" : ""}. Hasilnya
                    muncul di sini seketika.
                  </p>
                ) : (
                  <>
                    <p className="landing-calc-status">
                      {hasil.wajib
                        ? "Zakat yang perlu ditunaikan"
                        : hasil.cukupNisab
                          ? "Perkiraan bila haul sudah genap"
                          : "Belum wajib zakat"}
                    </p>
                    <p className={`landing-calc-amount${hasil.wajib ? "" : " is-tentative"}`}>
                      {rupiah(hasil.cukupNisab ? hasil.zakat : 0)}
                    </p>

                    <ul className="landing-calc-notes">
                      {hasil.pesan.map((pesan) => (
                        <li key={pesan.teks} className={`is-${pesan.nada}`}>
                          {pesan.teks}
                        </li>
                      ))}
                      {hasil.cukupNisab ? null : (
                        <li className="is-info">
                          Selama belum mencapai nisab, tidak ada kewajiban zakat yang perlu dibayar.
                        </li>
                      )}
                    </ul>

                    <dl className="landing-calc-detail">
                      {hasil.rincian.map(([label, value]) => (
                        <div key={label}>
                          <dt>{label}</dt>
                          <dd>{value}</dd>
                        </div>
                      ))}
                    </dl>

                    {hasil.cukupNisab ? (
                      <Link to="/login" className="landing-primary-button landing-calc-action">
                        Tunaikan lewat lembaga Anda <span aria-hidden="true">→</span>
                      </Link>
                    ) : null}

                    <p className="landing-calc-disclaimer">
                      Perkiraan berdasarkan kaidah yang umum dipakai. Nisab dan ketentuan tiap lembaga amil bisa
                      berbeda, jadi konfirmasikan kembali saat menunaikannya.
                    </p>
                  </>
                )}
              </aside>
            </div>
          </div>
        )}
      </Container>
    </section>
  );
};

export default ZakatCalculator;
