export class EnrollmentMessage {
    private email: string;
  
    constructor(email: string) {
      this.email = email;
    }
  
    getMessage() {
      return {
        to: this.email,
        cc: ["contact@eaea.ro", "stefan.bordei@eaea.ro"],
        from: {
          email: "alex.bordei@eaea.ro",
          name: "Alex Bordei",
        },
        subject:
          "Documente necesare înscriere - Module de curs Early Alpha Engineering",
        text: `Bună ziua,
  
  În primul rând, vă mulțumim pentru interesul manifestat față de programele educaționale oferite de Early Alpha Engineering. Ne bucurăm că doriți să vă înscrieți copiii în modulele noastre de curs și suntem aici pentru a vă ghida prin procesul de înscriere.
  
  Pentru a începe procedura, vă rugăm să completați Cererea de Înscriere disponibilă la următorul link: https://forms.gle/pNMXfHhDd98e9kGs6. După ce finalizați completarea formularului, vom proceda la redactarea contractului, care vă va fi furnizat în scop de analiză.
  
  Este important de menționat că documentul aferent contractului va fi trimis ulterior completarii fomularului din acest e-mail. Acesta este destinat exclusiv analizei dumneavoastră și nu necesită semnarea sau transmiterea înapoi în acest stadiu. Acesta este un pas preliminar, menit să vă ofere o înțelegere clară a termenilor și condițiilor participării la cursurile noastre.
  
  După analizarea și acceptarea condițiilor prezentate în contract, semnarea oficială a acestuia va avea loc la sediul nostru, în ziua primei ședințe de curs. Aceasta este o oportunitate excelentă pentru a ne cunoaște personal și pentru a discuta orice detalii sau nelămuriri.
  
  Pe noi ne găsiți la telefon 0761 131 636 sau la adresa unde ne desfășurăm activitatea: Strada Jiului 2A, Clădirea Tornado, etaj 1, camera 105, Sector 1, București.
  
  Dacă aveți întrebări suplimentare, solicitări specifice sau dacă doriți să discutăm mai multe despre modul în care cursurile noastre pot beneficia dezvoltarea copilului dvs., vă încurajăm să ne contactați. Echipa Early Alpha Engineering este întotdeauna disponibilă să vă asiste și să vă ofere toate informațiile necesare.
  
  Vă mulțumim încă o dată pentru alegerea de a face parte din comunitatea Early Alpha Engineering. Abia așteptăm să începem această călătorie împreună, explorând lumea fascinantă a ingineriei și a tehnologiei din spatele roboților.
  
  Cu respect,
  Alex Bordei
  Managing Partner
  
  E-mail: alex.bordei@eaea.ro
  Phone: +4 0721 114 056
  Early Alpha Engineering
  Strada Jiului 2A, Cladirea Tornado, etaj 1, camera 105, Sector 1, București
  www.eaea.ro`,
      };
    }
  }
  