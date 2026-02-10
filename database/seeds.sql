-- ============================================================
-- GMAO E-BONE — Seeds (Dades inicials de catàleg)
-- ============================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------
-- ROLS
-- -----------------------------------------------------------
INSERT INTO `rols` (`nom`, `descripcio`) VALUES
('superadmin', 'Accés total. Gestiona instal·lacions i usuaris globals.'),
('admin_instalacio', 'Administrador d''una instal·lació concreta. CRUD complet.'),
('cap_manteniment', 'Cap de manteniment. Planifica, assigna, registra execucions.'),
('tecnic', 'Tècnic de torn. Veu tasques assignades i registra execucions.'),
('lectura', 'Només lectura (direcció, auditoria).');

-- -----------------------------------------------------------
-- USUARI SUPERADMIN INICIAL (password: admin123)
-- -----------------------------------------------------------
INSERT INTO `usuaris` (`nom`, `cognoms`, `email`, `password_hash`, `actiu`, `is_superadmin`) VALUES
('Admin', 'GMAO', 'admin@gmao.local', '$2y$12$1yuu90N1PHMVzuh1Fw7DEuhF6Ff0ebBFw2pC5ifuK0eHNX/VCvsYa', 1, 1);

-- -----------------------------------------------------------
-- ESTATS EQUIP
-- -----------------------------------------------------------
INSERT INTO `estats_equip` (`nom`, `descripcio`, `ordre`) VALUES
('MB', 'Molt bé', 1),
('B', 'Bé', 2),
('R', 'Regular', 3),
('D', 'Deficient', 4),
('BAIXA', 'Equip donat de baixa', 5);

-- -----------------------------------------------------------
-- PERIODICITATS
-- -----------------------------------------------------------
INSERT INTO `periodicitats` (`nom`, `dies_interval`, `vegades_any`, `ordre`) VALUES
('diari', 1, 365.0000, 1),
('setmanal', 7, 52.1429, 2),
('quinzenal', 14, 26.0714, 3),
('mensual', 30, 12.0000, 4),
('bimestral', 61, 6.0000, 5),
('trimestral', 91, 4.0000, 6),
('quadrimestral', 122, 3.0000, 7),
('semestral', 183, 2.0000, 8),
('anual', 365, 1.0000, 9),
('biennal', 730, 0.5000, 10),
('quadriennal', 1461, 0.2500, 11),
('quinquennal', 1826, 0.2000, 12),
('decenal', 3652, 0.1000, 13);

-- -----------------------------------------------------------
-- SISTEMES (extrets de l'Excel BD TASQUES)
-- -----------------------------------------------------------
INSERT INTO `sistemes` (`codi`, `nom`) VALUES
('OCA', 'Organisme de Control Autoritzat'),
('AE', 'Anàlisis Externs'),
('EEXT', 'Empresa Externa'),
('ACS', 'Aigua Calenta Sanitària'),
('HP', 'Hidràulica Piscines'),
('BT', 'Baixa Tensió / Electricitat'),
('CL', 'Climatització'),
('NE', 'Neteja'),
('LE', 'Legionel·la'),
('AL', 'Lectures i Consums'),
('WLL', 'Wellness / Spa'),
('MQ', 'Maquinària'),
('AFCH', 'Aigua Freda de Consum Humà'),
('CI', 'Contra Incendis'),
('GN', 'Gas Natural'),
('EL', 'Elevadors'),
('SE', 'Seguretat'),
('PAV', 'Paviments'),
('EE', 'Equipament Esportiu');

-- -----------------------------------------------------------
-- TIPUS EQUIP (extrets de l'Excel INVENTARI)
-- -----------------------------------------------------------
INSERT INTO `tipus_equip` (`codi`, `nom`) VALUES
('BPR', 'Bomba de pressió / circulació'),
('CAL', 'Caldera'),
('VEX', 'Vas d''expansió'),
('ACU', 'Acumulador'),
('DEH', 'Deshumectadora'),
('BSE', 'Bomba secundària'),
('BES', 'Bescanviador de plaques'),
('CL', 'Climatitzadora'),
('AER', 'Aerotèrmia'),
('DIC', 'Dipòsit de compensació'),
('FIL', 'Filtre'),
('DOS', 'Dosificador'),
('SON', 'Sonda'),
('REG', 'Regulador'),
('QEL', 'Quadre elèctric'),
('BAT', 'Bateria de condensadors'),
('SAI', 'SAI / UPS'),
('GEL', 'Grup electrogen'),
('LAM', 'Lluminàries'),
('ASC', 'Ascensor'),
('EXT', 'Extintor'),
('BIE', 'Boca d''incendi equipada'),
('DET', 'Detector'),
('CEN', 'Central d''alarmes'),
('ROB', 'Robot piscina'),
('SAU', 'Sauna'),
('VAP', 'Bany de vapor'),
('JAC', 'Jacuzzi'),
('DUX', 'Dutxa'),
('CAD', 'Caldera biomassa');

-- -----------------------------------------------------------
-- NORMATIVES (extretes de l'Excel)
-- -----------------------------------------------------------
INSERT INTO `normatives` (`codi`, `nom`) VALUES
('RD1027_2007', 'R.D. 1027/2007, RITE'),
('RD842_2002', 'R.D. 842/2002, REBT'),
('RD742_2013', 'R.D. 742/2013, PISCINES'),
('RD865_2003', 'R.D. 865/2003, PREVENCIÓ LEGIONEL·LA'),
('D352_2004', 'Decret 352/2004, PREVENCIÓ LEGIONEL·LA'),
('D95_2000', 'DECRET 95/2000, PISCINES'),
('RD88_2013', 'R.D. 88/2013, ITC AEM 1'),
('RD513_2017', 'R.D. 513/2017, REGLAMENT CONTRAINCENDIS'),
('CTE_HE4', 'CTE 2006 DB-HE4'),
('PLA_AUTO', 'PLA AUTOPROTECCIÓ'),
('PLA_CTRL', 'PLA AUTOCONTROL');
