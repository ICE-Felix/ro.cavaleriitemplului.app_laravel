export class RenewSubscription {
        private email: string;
        private name: string;
        private invoiceUrl: string;
        private subscription: any;
  
        constructor(email: string, name: string, invoiceUrl: string, subscription: any) {
          this.email = email;
          this.name = name;
          this.invoiceUrl = invoiceUrl;
          this.subscription = subscription;
        }
  
        getMessage() {
          return {
            to: this.email,
            from: {
              email: "alex.bordei@eaea.ro",
              name: "Alex Bordei",
            },
            subject:
              `Noul abonament pentru ${this.subscription.student.first_name} ${this.subscription.student.last_name} la Early Alpha Engineering a fost generat`,
            text: `Bună ziua,

Vă mulțumim pentru colaborarea continuă cu Early Alpha Engineering și pentru susținerea parcursului educațional al copilului dumneavoastră în domeniul roboticii.

Dorim să vă informăm că abonamentul cu ID-ul ${this.subscription.id} a fost generat pentru perioada ${this.subscription.starting_date} - ${this.subscription.ending_date}. Factura aferentă acestui abonament este atașată acestui email. După achitarea acesteia, vom reveni cu factura fiscală corespunzătoare.

Modalități de plată:

Transfer bancar: Vă rugăm să includeți numele facturii în detaliile de plată.
Cash sau card: Plata poate fi efectuată direct la sediul școlii.

Mai jos regăsiți desfășurătorul sesiunilor:

${this.subscription.sessions.map((session, index) => 
  `${index === this.subscription.sessions.length - 1 ? 'Ultima sesiune din abonament' : 'Sesiunea'} #${index + 1}: ${this.subscription.student.first_name} ${this.subscription.student.last_name} – ${new Date(session.date).toLocaleDateString('ro-RO', {weekday: 'long'})}, ${new Date(session.date).toLocaleDateString('ro-RO', {day: '2-digit', month: '2-digit', year: 'numeric'})}, între ${session.starting_hour} - ${session.ending_hour}`).join('\n')}

Pentru a asigura o experiență plăcută tuturor participanților, vă rugăm să ne anunțați cu cel puțin 24 de ore în avans în cazul în care copilul dumneavoastră nu poate participa la o sesiune.

Ne puteți contacta pe:

WhatsApp: +40 761 131 636
Telefon: +40 761 131 636
Email: contact@eaea.ro

Vă mulțumim pentru alegerea de a continua colaborarea cu noi și pentru încrederea acordată în dezvoltarea pasiunii pentru robotică a copilului dumneavoastră. Ne bucurăm să facem parte din această călătorie educațională alături de familia dumneavoastră!

Cu stimă,
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