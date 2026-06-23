<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportarUsuariosLegacySeeder extends Seeder
{
    public function run(): void
    {
        $existentes = DB::table('persona')->where('person_codigo', 'like', 'LEGV_%')->count();
        if ($existentes > 0) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            $personaIds = DB::table('persona')->where('person_codigo', 'like', 'LEGV_%')->pluck('id_persona');
            $userIds    = DB::table('users')->whereIn('id_persona', $personaIds)->pluck('id_users');
            DB::table('model_has_roles')->whereIn('model_id', $userIds)->where('model_type', 'App\\Models\\User')->delete();
            DB::table('user_sucursal')->whereIn('id_users', $userIds)->delete();
            DB::table('users')->whereIn('id_users', $userIds)->delete();
            DB::table('persona')->whereIn('id_persona', $personaIds)->delete();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->command->warn("Se eliminaron $existentes registros previos incompletos antes de re-insertar.");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $now        = now()->toDateTimeString();
        $password   = Hash::make('123456');
        $idEmpresa  = DB::table('empresa')->value('id_empresa') ?? 1;
        $idSucursal = DB::table('sucursals')->value('id_sucursal');

        $vendors = [    ['codigo'=>'001','nick'=>'EARCENTALES','username'=>'earcentales','apepat'=>'ARCENTALES','apemat'=>'ESPINOZA','nombres'=>'ELDA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'002','nick'=>'RVERGARA','username'=>'rvergara','apepat'=>'VERGARA','apemat'=>'BROWN','nombres'=>'ROXANA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'003','nick'=>'LMENDOZA','username'=>'lmendoza','apepat'=>'MENDOZA','apemat'=>'VILLACORTA','nombres'=>'LAHIT','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'004','nick'=>'RCASTRO','username'=>'rcastro','apepat'=>'CASTRO','apemat'=>'A','nombres'=>'ROXANA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'005','nick'=>'MSANCHEZ','username'=>'msanchez','apepat'=>'SANCHEZ','apemat'=>'GUERRA','nombres'=>'MAYRA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'006','nick'=>'CMONCADA','username'=>'cmoncada','apepat'=>'MONCADA','apemat'=>'A','nombres'=>'CLEO','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'008','nick'=>'AAREVALO','username'=>'aarevalo','apepat'=>'AREVALO','apemat'=>'SINARAHUA','nombres'=>'ANTONIO','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'009','nick'=>'SOCAMPO','username'=>'socampo','apepat'=>'OCAMPO','apemat'=>'LOPEZ','nombres'=>'SUNNY MELYNA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'010','nick'=>'CKELITA','username'=>'ckelita','apepat'=>'CAHUACHI','apemat'=>'SOSA','nombres'=>'KELITA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'011','nick'=>'CTORRES','username'=>'ctorres','apepat'=>'TORRES','apemat'=>'FLORIANO','nombres'=>'MIGUELINA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'013','nick'=>'MTALEXIO','username'=>'mtalexio','apepat'=>'TALEXIO','apemat'=>'A','nombres'=>'MANUEL','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'014','nick'=>'GCOBOS','username'=>'gcobos','apepat'=>'COBOS','apemat'=>'REYNA','nombres'=>'GRECIA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'017','nick'=>'EMARAPARA','username'=>'emarapara','apepat'=>'MARAPARA','apemat'=>'HUANIO','nombres'=>'ENITH','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'018','nick'=>'ATORRES','username'=>'atorres','apepat'=>'TORRES','apemat'=>'APAGUEÑO','nombres'=>'AMBAR ANAIS','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'020','nick'=>'LZUMAETA','username'=>'lzumaeta','apepat'=>'ZUMAETA','apemat'=>'HUAYCAMA','nombres'=>'VANESSA L','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'021','nick'=>'AROMERO','username'=>'aromero','apepat'=>'ROMERO','apemat'=>'CISNERO','nombres'=>'ASTRID','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'023','nick'=>'EDIAZ','username'=>'ediaz','apepat'=>'DIAZ','apemat'=>'SOBRADO','nombres'=>'EDITH ELIZABETH','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'024','nick'=>'EPIÑA','username'=>'epi_a','apepat'=>'PIÑA','apemat'=>'GUERRA','nombres'=>'EUNICE AMP','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'026','nick'=>'MDELAGUILA','username'=>'mdelaguila','apepat'=>'DEL AGUILA','apemat'=>'RUIZ','nombres'=>'MARISELA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'028','nick'=>'MRODRIGUEZ','username'=>'mrodriguez','apepat'=>'RODRIGUEZ','apemat'=>'PAIMA','nombres'=>'MANUELA C','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'029','nick'=>'CFERREIRA','username'=>'cferreira','apepat'=>'FERREIRA','apemat'=>'IRUYARI','nombres'=>'CINTYA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'031','nick'=>'LMANAMA','username'=>'lmanama','apepat'=>'MANAMA','apemat'=>'A','nombres'=>'LISBET T','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'033','nick'=>'JGONGORA','username'=>'jgongora','apepat'=>'GONGORA','apemat'=>'A','nombres'=>'JOHANA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'034','nick'=>'KCANAQUIRI','username'=>'kcanaquiri','apepat'=>'CANAQUIRI','apemat'=>'ISUIZA','nombres'=>'KELLY','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'035','nick'=>'ICABANA','username'=>'icabana','apepat'=>'CABANA','apemat'=>'A','nombres'=>'INES','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'037','nick'=>'RZUCA','username'=>'rzuca','apepat'=>'ZUCA','apemat'=>'TOLEDO','nombres'=>'RUTH','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'038','nick'=>'ACURI','username'=>'acuri','apepat'=>'CURI','apemat'=>'CUESPAN','nombres'=>'ANY KAROL','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'040','nick'=>'ESTEFANI','username'=>'estefani','apepat'=>'JARAMILLO','apemat'=>'SORIA','nombres'=>'ESTEFANI','dni'=>'75809501','direccion'=>'MICAELA BASTIDAS #150','telefono'=>'','estado'=>1],
    ['codigo'=>'042','nick'=>'MDIAZ','username'=>'mdiaz','apepat'=>'DIAZ','apemat'=>'MARIN','nombres'=>'MAYRA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'043','nick'=>'RGUERRA','username'=>'rguerra','apepat'=>'GUERRA','apemat'=>'JIMENEZ','nombres'=>'ROCIO','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'045','nick'=>'DYAHUARCANI','username'=>'dyahuarcani','apepat'=>'YAHUARCANI','apemat'=>'CAHUAMARI','nombres'=>'DORIS','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'046','nick'=>'CSOLIS','username'=>'csolis','apepat'=>'SOLIS','apemat'=>'UPIACHIHUA','nombres'=>'CELESTE','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'047','nick'=>'DSANGAMA','username'=>'dsangama','apepat'=>'SANGAMA','apemat'=>'MOZOMBITE','nombres'=>'DANIA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'048','nick'=>'ECURMAYARI','username'=>'ecurmayari','apepat'=>'CURMAYARI','apemat'=>'CHOTA','nombres'=>'ELIANA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'050','nick'=>'IBEJARANO','username'=>'ibejarano','apepat'=>'BEJARANO','apemat'=>'LOPEZ','nombres'=>'IRMA J','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'058','nick'=>'VPAREDES','username'=>'vparedes','apepat'=>'PAREDES','apemat'=>'CASTRO','nombres'=>'VERONICA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'060','nick'=>'OFICINA','username'=>'oficina','apepat'=>'OFICINA','apemat'=>'OFICINA','nombres'=>'OFICINA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'062','nick'=>'JCALAGUA','username'=>'jcalagua','apepat'=>'CALAGUA','apemat'=>'ANGULO','nombres'=>'JEAN CARLO','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'068','nick'=>'MJIMENEZ','username'=>'mjimenez','apepat'=>'JIMENEZ','apemat'=>'NUÑEZ','nombres'=>'MAX','dni'=>'75446491','direccion'=>'AV.PARTICIPACION N°42 MZ-B SAN JUAN','telefono'=>'','estado'=>1],
    ['codigo'=>'070','nick'=>'KROJAS','username'=>'krojas','apepat'=>'ROJAS','apemat'=>'NASHNATE','nombres'=>'KAROLAY','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'071','nick'=>'RAYSITAR','username'=>'raysitar','apepat'=>'GUEVARA','apemat'=>'RIOS','nombres'=>'RAYSA M','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'072','nick'=>'HSORIA','username'=>'hsoria','apepat'=>'SORIA','apemat'=>'MENDOZA','nombres'=>'HEINZ','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'073','nick'=>'RFELICIDAD','username'=>'rfelicidad','apepat'=>'REVILLA','apemat'=>'ESCOBAR','nombres'=>'FELICIDAD','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'074','nick'=>'BRAMOS','username'=>'bramos','apepat'=>'RAMOS','apemat'=>'MEJA','nombres'=>'BRIGIDA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'075','nick'=>'JKOO','username'=>'jkoo','apepat'=>'KOO','apemat'=>'CORDOBA','nombres'=>'JORGE ABEL','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'076','nick'=>'CADRIANA','username'=>'cadriana','apepat'=>'COLOMA','apemat'=>'RENGIFO','nombres'=>'ADRIANA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'077','nick'=>'IANDRADE','username'=>'iandrade','apepat'=>'ANDRADE','apemat'=>'AREVALO','nombres'=>'IVONNE','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'078','nick'=>'ETELLO','username'=>'etello','apepat'=>'TELLO','apemat'=>'NUÑEZ','nombres'=>'ESTHER','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'079','nick'=>'LINGA','username'=>'linga','apepat'=>'INGA','apemat'=>'SULLON','nombres'=>'LIZ','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'080','nick'=>'PIBONE','username'=>'pibone','apepat'=>'CHAVEZ','apemat'=>'PAIMA','nombres'=>'IBONE','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'081','nick'=>'COCHAVANO','username'=>'cochavano','apepat'=>'OCHAVANO','apemat'=>'PANAIFO','nombres'=>'CARMEN','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'082','nick'=>'NORMAMANI','username'=>'normamani','apepat'=>'MAMANI','apemat'=>'LOPEZ','nombres'=>'NORMA YESENIA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'083','nick'=>'RUTHDEZA','username'=>'ruthdeza','apepat'=>'DEZA','apemat'=>'JIMENEZ','nombres'=>'RUTH','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'084','nick'=>'FLORBARDA','username'=>'florbarda','apepat'=>'BARDALES','apemat'=>'PACAYA','nombres'=>'FLOR DE MARIA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'085','nick'=>'JLAZO','username'=>'jlazo','apepat'=>'LAZO','apemat'=>'ACHING','nombres'=>'JOSE F','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'086','nick'=>'MCURINUQUI','username'=>'mcurinuqui','apepat'=>'MARIA','apemat'=>'CURINUQUI','nombres'=>'PEREZ','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'087','nick'=>'PMACEDO','username'=>'pmacedo','apepat'=>'PATRICIA','apemat'=>'MACEDO','nombres'=>'RUIZ','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'088','nick'=>'LMORENO','username'=>'lmoreno','apepat'=>'MORENO','apemat'=>'PIÑA','nombres'=>'LUIS MIGUEL','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'089','nick'=>'VCAHUAZA','username'=>'vcahuaza','apepat'=>'CAHUAZA','apemat'=>'PEÑA','nombres'=>'VALERIA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'090','nick'=>'CALARCON','username'=>'calarcon','apepat'=>'ALARCON','apemat'=>'CAVALCANTE','nombres'=>'CECILIA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'091','nick'=>'FIOVASQUEZ','username'=>'fiovasquez','apepat'=>'VASQUEZ','apemat'=>'ZAMBRANO','nombres'=>'FIORELLA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'092','nick'=>'MMOZOMBITE','username'=>'mmozombite','apepat'=>'MOZOMBITE','apemat'=>'NAVARRO','nombres'=>'MAGVIS','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'093','nick'=>'MIMACUYAMA','username'=>'mimacuyama','apepat'=>'MACUYAMA','apemat'=>'AHUANARI','nombres'=>'BERTHA M','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'094','nick'=>'MOVASNA','username'=>'movasna','apepat'=>'VASQUEZ','apemat'=>'NAVARRO','nombres'=>'MORELIA M','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'095','nick'=>'XIVARGAS','username'=>'xivargas','apepat'=>'VARGAS','apemat'=>'LOPEZ','nombres'=>'XIOMY','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'096','nick'=>'MESTHER','username'=>'mesther','apepat'=>'MALAFAYA','apemat'=>'HUAYAMBA','nombres'=>'ESTHER','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'097','nick'=>'EREATEGUI','username'=>'ereategui','apepat'=>'REATEGUI','apemat'=>'VASQUEZ','nombres'=>'ERIKA JOHANA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'098','nick'=>'DILIA','username'=>'dilia','apepat'=>'WENINGER','apemat'=>'GARCIA','nombres'=>'DILIA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'099','nick'=>'BETHALVAN','username'=>'bethalvan','apepat'=>'ALVAN','apemat'=>'MANIHUARI','nombres'=>'BETHSABE','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'100','nick'=>'FIOVARGAS','username'=>'fiovargas','apepat'=>'VARGAS','apemat'=>'MOZOMBITE','nombres'=>'DAMARIS FIORELLA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'101','nick'=>'ACHILICAHUA','username'=>'achilicahua','apepat'=>'CHILICAHUA','apemat'=>'CASIMIRO','nombres'=>'ANTONIA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'102','nick'=>'JVILLACORTA','username'=>'jvillacorta','apepat'=>'VILLACORTA','apemat'=>'HURINUQI','nombres'=>'JUNIOR','dni'=>'75105134','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'103','nick'=>'KVALLES','username'=>'kvalles','apepat'=>'VALLES','apemat'=>'UPIACHIHUA','nombres'=>'KAREN PATRICIA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'104','nick'=>'ATICONA','username'=>'aticona','apepat'=>'TICONA','apemat'=>'HUAYTA','nombres'=>'ANANELIA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'105','nick'=>'JTORREA','username'=>'jtorrea','apepat'=>'TORREALVA','apemat'=>'ARREGUI','nombres'=>'ROSARIO','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'106','nick'=>'EDOMINGUEZ','username'=>'edominguez','apepat'=>'DOMINGUEZ','apemat'=>'AHUANARI','nombres'=>'ESTELA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'107','nick'=>'ASANTANA','username'=>'asantana','apepat'=>'SANTANA','apemat'=>'PACAYA','nombres'=>'ADRIANA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'108','nick'=>'FIOCACHIQUE','username'=>'fiocachique','apepat'=>'CACHIQUE','apemat'=>'REATEGUI','nombres'=>'GREYSI FIORELLA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'109','nick'=>'YENY','username'=>'yeny','apepat'=>'MACHACA','apemat'=>'CONDORI','nombres'=>'YENY','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'110','nick'=>'SSHIRLEY','username'=>'sshirley','apepat'=>'SANCHEZ','apemat'=>'HUANCA','nombres'=>'SHIRLEY VIOLETA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'111','nick'=>'CJIMENA','username'=>'cjimena','apepat'=>'CARCAUSTO','apemat'=>'PUMA','nombres'=>'JIMENA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'112','nick'=>'ELMERB','username'=>'elmerb','apepat'=>'BAZAN','apemat'=>'RENGIFO','nombres'=>'ELMER','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'113','nick'=>'NORALUZ','username'=>'noraluz','apepat'=>'MULLISACA','apemat'=>'AYARQUISPE','nombres'=>'NORA LUZ','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'114','nick'=>'JCARLOS','username'=>'jcarlos','apepat'=>'MUÑOZ','apemat'=>'AGUINDA','nombres'=>'CARLOS','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'115','nick'=>'JEAPAZA','username'=>'jeapaza','apepat'=>'APAZA','apemat'=>'JARA','nombres'=>'JESICA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'116','nick'=>'EDQUISPE','username'=>'edquispe','apepat'=>'QUISPE','apemat'=>'VILCA','nombres'=>'EDIHT AIDA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'117','nick'=>'RUSCALLE','username'=>'ruscalle','apepat'=>'CALLE','apemat'=>'PHOCCO','nombres'=>'RUSBI ALEXANDRA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'118','nick'=>'KMURAYARI','username'=>'kmurayari','apepat'=>'MURAYARI','apemat'=>'IJUMA','nombres'=>'KAREN','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'119','nick'=>'CLLERENA','username'=>'cllerena','apepat'=>'LLERENA','apemat'=>'VILCHEZ','nombres'=>'CHERRY','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'120','nick'=>'RCABRERA','username'=>'rcabrera','apepat'=>'CABRERA','apemat'=>'MELENDEZ','nombres'=>'RUBI DANIELA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'121','nick'=>'JGONZALES','username'=>'jgonzales','apepat'=>'GONZALES','apemat'=>'GUERRA','nombres'=>'JHOANA PATRICIA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'122','nick'=>'ESTEBANS','username'=>'estebans','apepat'=>'SAHUARICO','apemat'=>'MANIHUARI','nombres'=>'ESTEBAN','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'123','nick'=>'RCOBOS','username'=>'rcobos','apepat'=>'COBOS','apemat'=>'PIZANGO','nombres'=>'RUBIEL','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'124','nick'=>'MPASHANASTE','username'=>'mpashanaste','apepat'=>'PASHANASTE','apemat'=>'MAITAHUARI','nombres'=>'MILAGROS','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'125','nick'=>'BRODRIGUEZ','username'=>'brodriguez','apepat'=>'RODRIGUEZ','apemat'=>'SALDAÑA','nombres'=>'BRENDI','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'126','nick'=>'BETSIOCHOA','username'=>'betsiochoa','apepat'=>'OCHOA','apemat'=>'MENDEZ','nombres'=>'BETSI MARIVEL','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'127','nick'=>'APEZO','username'=>'apezo','apepat'=>'LINARES','apemat'=>'PEZO','nombres'=>'ALMENDRA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'128','nick'=>'APINEDO','username'=>'apinedo','apepat'=>'PINEDO','apemat'=>'CABUDIVO','nombres'=>'ALEJANDRA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'129','nick'=>'HMILAGROS','username'=>'hmilagros','apepat'=>'HUAMAN','apemat'=>'CHOQUEPATA','nombres'=>'MILAGROS','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'130','nick'=>'JRVILLACORTA','username'=>'jrvillacorta','apepat'=>'VILLACORTA','apemat'=>'CURINUQUI','nombres'=>'JOSE LUIS','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'131','nick'=>'SUCHARPEN','username'=>'sucharpen','apepat'=>'CHARPENTIER','apemat'=>'SANDI','nombres'=>'SUSANA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'132','nick'=>'EHUAYTA','username'=>'ehuayta','apepat'=>'HUAYTA','apemat'=>'TICONA','nombres'=>'ELENA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'133','nick'=>'BFABRCIO','username'=>'bfabrcio','apepat'=>'BOCANEGRA','apemat'=>'TORRES','nombres'=>'FABRICIO','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'134','nick'=>'FMAMANI','username'=>'fmamani','apepat'=>'MAMANI','apemat'=>'SUCARI','nombres'=>'FLOR','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'135','nick'=>'TMAMANI','username'=>'tmamani','apepat'=>'MAMANI','apemat'=>'CUEVA','nombres'=>'MARY THALIA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'136','nick'=>'LFUENTES','username'=>'lfuentes','apepat'=>'FUENTES','apemat'=>'TICONA','nombres'=>'LINDA MARICIELO','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'137','nick'=>'MILAGROSMT','username'=>'milagrosmt','apepat'=>'MAMANI','apemat'=>'TURPO','nombres'=>'MILAGROS','dni'=>'73352503','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'138','nick'=>'IRENE123','username'=>'irene123','apepat'=>'HUACOTO','apemat'=>'PARIAPAZA','nombres'=>'IRENE','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'139','nick'=>'CHGARCIA','username'=>'chgarcia','apepat'=>'GARCIA','apemat'=>'RAMOS','nombres'=>'CHANEL','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'140','nick'=>'ANAPEREZ','username'=>'anaperez','apepat'=>'PEREZ','apemat'=>'LARREA','nombres'=>'ANA','dni'=>'75706927','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'141','nick'=>'DMAYRA','username'=>'dmayra','apepat'=>'DIAZ','apemat'=>'MARIN','nombres'=>'MAYRA IBETH','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'142','nick'=>'RGREYSI','username'=>'rgreysi','apepat'=>'RAMIREZ','apemat'=>'ARIMUYA','nombres'=>'GREYSI','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'143','nick'=>'CVERONICA','username'=>'cveronica','apepat'=>'CONDORI','apemat'=>'MOROCCO','nombres'=>'VERONICA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'144','nick'=>'DELAGUILA','username'=>'delaguila','apepat'=>'DEL AGUILA','apemat'=>'GARCIA','nombres'=>'KAREN','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'145','nick'=>'AZUMAETA','username'=>'azumaeta','apepat'=>'ZUMAETA','apemat'=>'PALLA','nombres'=>'ARELIX','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'146','nick'=>'AGUEDES','username'=>'aguedes','apepat'=>'GUEDES','apemat'=>'ZAMBRANO','nombres'=>'AMILUZ','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'147','nick'=>'ZCHARAJA','username'=>'zcharaja','apepat'=>'CHARAJA','apemat'=>'CORNEJO','nombres'=>'ZENAYDA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'148','nick'=>'MCURASI','username'=>'mcurasi','apepat'=>'CURASI','apemat'=>'CUTIPA','nombres'=>'MERY','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'149','nick'=>'ALEYDE','username'=>'aleyde','apepat'=>'APAZA','apemat'=>'ROJAS','nombres'=>'LEYDE RAQUEL','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'150','nick'=>'MYIUGIA','username'=>'myiugia','apepat'=>'MACHACA','apemat'=>'CONDORI','nombres'=>'YIURGIA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'151','nick'=>'HROXANA','username'=>'hroxana','apepat'=>'HALLASI','apemat'=>'YUPA','nombres'=>'ROXANA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'152','nick'=>'FLORALEXIA','username'=>'floralexia','apepat'=>'MAMANI','apemat'=>'MAMANI','nombres'=>'FLOR ALEXIA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'153','nick'=>'YQUISPE','username'=>'yquispe','apepat'=>'QUISPE','apemat'=>'TRUJILLO','nombres'=>'ROSA YANETH','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'154','nick'=>'EMASCO','username'=>'emasco','apepat'=>'MASCO','apemat'=>'VILCA','nombres'=>'ELIZABETH ESMERALDA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'155','nick'=>'SGARCIA','username'=>'sgarcia','apepat'=>'GARCIA','apemat'=>'PAIMA','nombres'=>'SELVA','dni'=>'48762207','direccion'=>'LORETO Nº 941','telefono'=>'','estado'=>1],
    ['codigo'=>'156','nick'=>'DRICOPA','username'=>'dricopa','apepat'=>'RICOPA','apemat'=>'GONZALES','nombres'=>'JUAN DANIEL','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'157','nick'=>'SMALAFAYA','username'=>'smalafaya','apepat'=>'ANDRADE','apemat'=>'MALAFAYA','nombres'=>'SALVI ESTEFANI','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'166','nick'=>'KELITA','username'=>'kelita','apepat'=>'SOSA','apemat'=>'CAHUACHI','nombres'=>'KELITA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'158','nick'=>'ATIHUAY','username'=>'atihuay','apepat'=>'TIHUAY','apemat'=>'TAPULLIMA','nombres'=>'ALEJANDRINA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'159','nick'=>'MTAPAYURI','username'=>'mtapayuri','apepat'=>'YPUSHIMA','apemat'=>'TAPAYURI','nombres'=>'MARCY CAROL','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'160','nick'=>'ACHONG','username'=>'achong','apepat'=>'ACHONG','apemat'=>'CASEMIRO','nombres'=>'CESIA ORELLY','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'161','nick'=>'CVILLACORTA','username'=>'cvillacorta','apepat'=>'ECHEVARRIA','apemat'=>'VILLACORTA','nombres'=>'COLETH AMPARO','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'162','nick'=>'RAMANQUI','username'=>'ramanqui','apepat'=>'AYAMAMANI','apemat'=>'AMANQUI','nombres'=>'RUTH CELENIA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>0],
    ['codigo'=>'163','nick'=>'RPEREZ','username'=>'rperez','apepat'=>'PEREZ','apemat'=>'PAZ','nombres'=>'RAUL','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'164','nick'=>'GPANAIFO','username'=>'gpanaifo','apepat'=>'PANAIFO','apemat'=>'MURAYARI','nombres'=>'GREYSI','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'165','nick'=>'RBARBARAN','username'=>'rbarbaran','apepat'=>'PINO','apemat'=>'BARBARAN','nombres'=>'ROSA MARIA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'167','nick'=>'VPANDURO','username'=>'vpanduro','apepat'=>'PANDURO','apemat'=>'RIOS','nombres'=>'VALERIEN DEL CARMEN','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'168','nick'=>'ACRISTINA','username'=>'acristina','apepat'=>'ANDRADE','apemat'=>'OLORTEQUI','nombres'=>'KATERIN CRISTINA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'169','nick'=>'ISAAC','username'=>'isaac','apepat'=>'SANGAMA','apemat'=>'AMASIFUEN','nombres'=>'PERCY ISAAC','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'170','nick'=>'YCACERES','username'=>'ycaceres','apepat'=>'CACERES','apemat'=>'CAYO','nombres'=>'YIAMIL','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'171','nick'=>'GEO','username'=>'geo','apepat'=>'PINEDA','apemat'=>'JIHUALLANCCA','nombres'=>'GEOVANNA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'172','nick'=>'DINA','username'=>'dina','apepat'=>'BOCANEGRA','apemat'=>'CORDOVA','nombres'=>'DINA','dni'=>'72899424','direccion'=>'CA. BENAVIDES #8','telefono'=>'','estado'=>1],
    ['codigo'=>'173','nick'=>'MARJORIE','username'=>'marjorie','apepat'=>'SOPLIN','apemat'=>'VILCHEZ','nombres'=>'MARJORIE','dni'=>'76655740','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'174','nick'=>'JOSE','username'=>'jose','apepat'=>'AYACHI','apemat'=>'CEGUA','nombres'=>'JOSE ARMANDO','dni'=>'72198858','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'175','nick'=>'CARLOSV','username'=>'carlosv','apepat'=>'CORDERO','apemat'=>'TAMANI','nombres'=>'VICTOR CARLOS','dni'=>'75281184','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'176','nick'=>'YESSICA','username'=>'yessica','apepat'=>'MAMANI','apemat'=>'HUARACHA','nombres'=>'YESSICA','dni'=>'73581125','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'177','nick'=>'CRISTAL','username'=>'cristal','apepat'=>'VERGARA','apemat'=>'SANGAMA','nombres'=>'CRISTAL','dni'=>'72161398','direccion'=>'SAN ANTONIO C/PJE CONDOR Nº 190','telefono'=>'','estado'=>1],
    ['codigo'=>'178','nick'=>'EDWIN','username'=>'edwin','apepat'=>'REATEGUI','apemat'=>'MAHUA','nombres'=>'EDWIN','dni'=>'77082752','direccion'=>'AV. VILLA SELVA / MZ G LOTE 19','telefono'=>'','estado'=>1],
    ['codigo'=>'179','nick'=>'AARCENTALES','username'=>'aarcentales','apepat'=>'PEREIRA','apemat'=>'ARCENTALES','nombres'=>'ANGEL CHRISTIAN','dni'=>'70368475','direccion'=>'AV. 28 DE JULIO 501','telefono'=>'','estado'=>0],
    ['codigo'=>'180','nick'=>'PSAJAMI','username'=>'psajami','apepat'=>'PAULO','apemat'=>'SAJAMI','nombres'=>'ARIMUYA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'181','nick'=>'CSAMANTA','username'=>'csamanta','apepat'=>'CABUDIVO','apemat'=>'CASIMIRO','nombres'=>'SAMANTA','dni'=>'74841354','direccion'=>'PARTICIPACION C/SHIRINGA MZA N LOTE 15','telefono'=>'','estado'=>0],
    ['codigo'=>'182','nick'=>'EPAMELA','username'=>'epamela','apepat'=>'CCOA','apemat'=>'PARI','nombres'=>'ESTEFANI PAMELA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'183','nick'=>'MLISBETH','username'=>'mlisbeth','apepat'=>'MAMANI','apemat'=>'VILCA','nombres'=>'LISBETH','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'184','nick'=>'GDEISY','username'=>'gdeisy','apepat'=>'CONDORI','apemat'=>'COLQUEHUANCA','nombres'=>'GILDA DEISY','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'185','nick'=>'MJANETH','username'=>'mjaneth','apepat'=>'MAMANI','apemat'=>'LUQUE','nombres'=>'JANETH','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'186','nick'=>'GANDREA','username'=>'gandrea','apepat'=>'GUILLEN','apemat'=>'RODRIGUEZ','nombres'=>'ANDREA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'187','nick'=>'OSAMELI','username'=>'osameli','apepat'=>'OJANAMA','apemat'=>'TORRES','nombres'=>'SAMELI','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'188','nick'=>'LEYDI','username'=>'leydi','apepat'=>'CACERES','apemat'=>'CAYO','nombres'=>'LEYDI','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'189','nick'=>'YPINEDO','username'=>'ypinedo','apepat'=>'PINEDO','apemat'=>'APAGUEÑO','nombres'=>'YASIRA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'190','nick'=>'SIGNASIO','username'=>'signasio','apepat'=>'SHUÑA','apemat'=>'RODRIGUEZ','nombres'=>'IGNACIO','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'191','nick'=>'GCARPIO','username'=>'gcarpio','apepat'=>'CARPIO','apemat'=>'CANAYO','nombres'=>'GINA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'192','nick'=>'NICOL','username'=>'nicol','apepat'=>'OROCHE','apemat'=>'RUIZ','nombres'=>'NICOL','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'193','nick'=>'CLAUDIA','username'=>'claudia','apepat'=>'SILVANO','apemat'=>'TAMANI','nombres'=>'CLAUDIA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'194','nick'=>'RAQUEL','username'=>'raquel','apepat'=>'APAZA','apemat'=>'ROJAS','nombres'=>'RAQUEL','dni'=>'47616374','direccion'=>'URB.CANCOLLANI','telefono'=>'950737806','estado'=>1],
    ['codigo'=>'195','nick'=>'QNATI','username'=>'qnati','apepat'=>'QUISPE','apemat'=>'TORRES','nombres'=>'NATI','dni'=>'42358513','direccion'=>'','telefono'=>'900992241','estado'=>1],
    ['codigo'=>'196','nick'=>'RSACSI','username'=>'rsacsi','apepat'=>'SACSI','apemat'=>'SACSI','nombres'=>'ROSA MARIA','dni'=>'63284147','direccion'=>'','telefono'=>'970962380','estado'=>1],
    ['codigo'=>'197','nick'=>'GUILLEN','username'=>'guillen','apepat'=>'GUILLEN','apemat'=>'VARGAS','nombres'=>'GRECIA','dni'=>'77755562','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'198','nick'=>'DAMARIS','username'=>'damaris','apepat'=>'PILCO','apemat'=>'SUCACAHUA','nombres'=>'DAMARIS','dni'=>'77200847','direccion'=>'','telefono'=>'910701697','estado'=>1],
    ['codigo'=>'199','nick'=>'ANA','username'=>'ana','apepat'=>'MACEDO','apemat'=>'GUERRA','nombres'=>'ANA MARIA','dni'=>'45721881','direccion'=>'','telefono'=>'971816426','estado'=>1],
    ['codigo'=>'200','nick'=>'ELIANA','username'=>'eliana','apepat'=>'MAMANI','apemat'=>'MAMANI','nombres'=>'NELY ELIANA','dni'=>'73430287','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'201','nick'=>'ECHAVEZ','username'=>'echavez','apepat'=>'CHAVEZ','apemat'=>'GUERRA','nombres'=>'EVOLI','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'202','nick'=>'CCAMILA','username'=>'ccamila','apepat'=>'CONDORI','apemat'=>'PROVINCIA','nombres'=>'LUISA CAMILA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'203','nick'=>'MARIA','username'=>'maria','apepat'=>'AHPANCHO','apemat'=>'CUELLAR','nombres'=>'MARIA EUGENIA','dni'=>'46326164','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'204','nick'=>'GINA','username'=>'gina','apepat'=>'MARAPARA','apemat'=>'ISUIZA','nombres'=>'GINA KEILA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'205','nick'=>'HCARRANZA','username'=>'hcarranza','apepat'=>'CARRANZA','apemat'=>'ARIRAMA','nombres'=>'HOMER','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'206','nick'=>'ALMENDRA','username'=>'almendra','apepat'=>'TORRES','apemat'=>'MARAPARA','nombres'=>'GRACIA ALMENDRA','dni'=>'75590913','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'207','nick'=>'ROSA','username'=>'rosa','apepat'=>'COQUINCHE','apemat'=>'COQUINCHE','nombres'=>'ROSA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'208','nick'=>'FVIVIANA','username'=>'fviviana','apepat'=>'NASHNATE','apemat'=>'FLORES','nombres'=>'VIVIANA','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'209','nick'=>'PGLORIA','username'=>'pgloria','apepat'=>'PEÑA','apemat'=>'PUA','nombres'=>'GLORIA DEL CARMEN','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'210','nick'=>'RJHON','username'=>'rjhon','apepat'=>'RENGIFO','apemat'=>'ARIRAMA','nombres'=>'JHON WILLY','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'211','nick'=>'CHISTAMA','username'=>'chistama','apepat'=>'CHISTAMA','apemat'=>'ARIRAMA','nombres'=>'JENNIFER','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'212','nick'=>'MSALCEDO','username'=>'msalcedo','apepat'=>'SALCEDO','apemat'=>'CCANA','nombres'=>'MEDALIT','dni'=>'47697764','direccion'=>'','telefono'=>'','estado'=>1],
    ['codigo'=>'213','nick'=>'VALLES','username'=>'valles','apepat'=>'VALLES','apemat'=>'UPIACHIHUA','nombres'=>'KAREN','dni'=>'','direccion'=>'','telefono'=>'','estado'=>1],
        ];

        foreach ($vendors as $v) {
            $nick = strtolower($v['username']);

            $personaId = DB::table('persona')->insertGetId([
                'id_empresa'               => $idEmpresa,
                'persona_nombre'           => $v['nombres'],
                'persona_apellido_paterno' => $v['apepat'],
                'persona_apellido_materno' => $v['apemat'] ?: null,
                'persona_email'            => null,
                'persona_tipo_documento'   => '1',
                'persona_dni'              => $v['dni'] ?: ('LEGV'.$v['codigo']),
                'persona_telefono'         => $v['telefono'] ?: null,
                'persona_direccion'        => $v['direccion'] ?: null,
                'persona_blacklist'        => 'NO',
                'persona_empleado'         => 1,
                'person_codigo'            => 'LEGV_'.$v['codigo'],
                'persona_estado'           => 1,
                'created_at'               => $now,
                'updated_at'               => $now,
            ]);

            $userId = DB::table('users')->insertGetId([
                'nombre_users'    => $v['nombres'],
                'email'           => strtolower($v['nick']).'@mundo-fantasia.com',
                'password'        => $password,
                'username'        => $nick,
                'user_fotografia' => 'sin-fotografia.png',
                'id_persona'      => $personaId,
                'users_estado'    => $v['estado'],
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            DB::table('model_has_roles')->insert([
                'role_id'    => 3,
                'model_type' => 'App\\Models\\User',
                'model_id'   => $userId,
            ]);

            if ($idSucursal) {
                DB::table('user_sucursal')->insert([
                    'id_users'    => $userId,
                    'id_sucursal' => $idSucursal,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
