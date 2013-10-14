<?php
function pubmed_fetch($query){

  $url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi';
  
  $params = array(
    'db' => 'pubmed',
    'retmode' => 'xml',
    'retmax' => 1,
    'usehistory' => 'y',
    'term' => urlencode($query),
    );

  /*
  $url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?' . http_build_query($params);
  $xml = simplexml_load_file($url);
  */
  //url-ify the data for the POST
  foreach($params as $key=>$value) {
  	$params_string .= $key.'='.$value.'&';
  }
  rtrim($params_string, '&');
  
  //open connection
  $ch = curl_init();
  
  // Set the url, number of POST vars, POST data
  curl_setopt($ch,CURLOPT_URL, $url);
  curl_setopt($ch,CURLOPT_POST, count($params));
  curl_setopt($ch,CURLOPT_POSTFIELDS, $params_string);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  
  $response = curl_exec($ch);
  $xml = simplexml_load_string($response); // Response needs to be trimmed because there were numbers at the end that made the response invalid xml. I could not find out why these numbers were there.
  
  //$xml = simplexml_load_string(rtrim($response, "1..9")); // Response needs to be trimmed because there were numbers at the end that made the response invalid xml. I could not find out why these numbers were there.
  
  curl_close($ch);

  pubmed_errors($xml);

  $count = (int) $xml->Count;
  print "$count items found\n";

  /*  
  $translated = (string) $xml->QueryTranslation;
  printf("Translated query: %s\n\n", $translated);
  */

  $retmax = 10000; // Maximum number of entries returned per request
  $maxretry = 10; // Maximum number of retries for failed queries
  $sleeptime = 30; // Time to sleep between requests (seconds)

  for ($retstart = 0; $retstart < $count; $retstart += $retmax) {

      $params = array(
        'db' => 'pubmed',
        'retmode' => 'xml',
        'query_key' => (string) $xml->QueryKey,
        'WebEnv' => (string) $xml->WebEnv,
        'retmax' => $retmax,
        'retstart' => $retstart
      );

      for ($try_number = 0; $try_number < $maxretry; $try_number++) {
	      sleep($sleeptime);
		  $url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?' . http_build_query($params);
		  $file = "./pubmed/pubmed_results_" . $retstart. ".xml";
		  print ("\n Filesize of $file is " . filesize($file) . "\n");
		  clearstatcache(); // Needed because otherwise changed filesizes would be ignored...
		  if (filesize($file) > 200) break; // We assume that the request was successful if the file size is above 200 bytes. No further retries necessary.
		  system(sprintf("wget --output-document=%s %s", escapeshellarg($file), escapeshellarg($url)));
      }
  }
}

function pubmed_errors($xml){
  print "\033[31m"; // red

  if ($xml->ErrorList){
    if ($xml->ErrorList->PhraseNotFound)
      printf("Phrase not found: %s\n", (string) $xml->ErrorList->PhraseNotFound);
    if ($xml->ErrorList->FieldNotFound)
      printf("Field not found: %s\n", (string) $xml->ErrorList->FieldNotFound);
  }

  if ($xml->WarningList){
    print (string) $xml->WarningList->OutputMessage . "\n";
    if ($xml->WarningList->QuotedPhraseNotFound)
      printf("Quoted phrase not found: %s\n", (string) $xml->WarningList->QuotedPhraseNotFound);
    if ($xml->WarningList->PhraseIgnored)
      printf("Phrase ignored: %s\n", (string) $xml->WarningList->PhraseIgnored);
  }

  print "\033[00m"; // default
}

///////////////////////////////////////////////////////////

$query = <<<'END_OF_STRING'
"humans"[MeSH Terms] AND 
(("2005/01/01"[Date - Publication] : "3000/01/01"[Date - Publication])) 
AND "has abstract"[Filter]
NOT "biography"[Publication Type]
NOT "comment"[Publication Type]
NOT "editorial"[Publication Type]
AND
(
"Acad Emerg Med"[Journal] OR 
"Acta Obstet Gynecol Scand"[Journal] OR 
"Acta Paediatr"[Journal] OR 
"Acupunct Electrother Res"[Journal] OR 
"Acupunct Med"[Journal] OR 
"Addiction"[Journal] OR 
"Age Ageing"[Journal] OR 
"AIDS"[Journal] OR 
"AIDS Patient Care STDs"[Journal] OR 
"AIDS Res Ther"[Journal] OR 
"AJR Am J Roentgenol"[Journal] OR 
"Aliment Pharmacol Ther"[Journal] OR 
"Allergy"[Journal] OR 
"Allergy Asthma Proc"[Journal] OR 
"Am Heart J"[Journal] OR 
"Am J Acupunct"[Journal] OR 
"Am J Cardiol"[Journal] OR 
"Am J Clin Dermatol"[Journal] OR 
"Am J Epidemiol"[Journal] OR 
"Am J Health Syst Pharm"[Journal] OR 
"Am J Hematol"[Journal] OR 
"Am J Hosp Palliat Care"[Journal] OR 
"Am J Kidney Dis"[Journal] OR 
"Am J Obstet Gynecol"[Journal] OR 
"Am J Ophthalmol"[Journal] OR 
"Am J Phys Med Rehabil"[Journal] OR 
"Am J Physiol Endocrinol Metab"[Journal] OR 
"Am J Physiol Heart Circ Physiol"[Journal] OR 
"Am J Public Health"[Journal] OR 
"Am J Respir Crit Care Med"[Journal] OR 
"Am J Surg"[Journal] OR 
"Am J Trop Med Hyg"[Journal] OR 
"Andrologia"[Journal] OR 
"Anesth Analg"[Journal] OR 
"Anesthesiology"[Journal] OR 
"Ann Allergy Asthma Immunol"[Journal] OR 
"Ann Clin Microbiol Antimicrob"[Journal] OR 
"Ann Emerg Med"[Journal] OR 
"Ann Fam Med"[Journal] OR 
"Ann Gen Psychiatry"[Journal] OR 
"Ann Neurol"[Journal] OR 
"Ann Oncol"[Journal] OR 
"Ann Pharmacother"[Journal] OR 
"Ann Plast Surg"[Journal] OR 
"Ann Surg"[Journal] OR 
"Ann Surg Innov Res"[Journal] OR 
"Ann Surg Oncol"[Journal] OR 
"Ann Rheum Dis"[Journal] OR 
"Antimicrob Agents Chemother"[Journal] OR 
"Appl Psychophysiol Biofeedback"[Journal] OR 
"Arch Dermatol"[Journal] OR 
"Arch Dis Child"[Journal] OR 
"Arch Dis Child Fetal Neonatal Ed"[Journal] OR 
"Arch Gen Psychiatry"[Journal] OR 
"Arch Neurol"[Journal] OR 
"Arch Ophthalmol"[Journal] OR 
"Arch Otolaryngol Head Neck Surg"[Journal] OR 
"Arch Pediatr Adolesc Med"[Journal] OR 
"Arch Phys Med Rehabil"[Journal] OR 
"Arch Surg"[Journal] OR 
"Arthritis Rheum"[Journal] OR 
"Arthritis Care Res [Hoboken]"[Journal] OR 
"Arthritis Res Ther"[Journal] OR 
"Arthroscopy"[Journal] OR 
"Behav Brain Funct"[Journal] OR 
"Biol Psychiatry"[Journal] OR 
"Biopsychosoc Med"[Journal] OR 
"BJOG"[Journal] OR 
"BJU Int"[Journal] OR 
"Blood"[Journal] OR 
"BMC Anesthesiol"[Journal] OR 
"BMC Blood Disord"[Journal] OR 
"BMC Cancer"[Journal] OR 
"BMC Cardiovasc Disord"[Journal] OR 
"BMC Clin Pathol"[Journal] OR 
"BMC Clin Pharmacol"[Journal] OR 
"BMC Dermatol"[Journal] OR 
"BMC Ear Nose Throat Disord"[Journal] OR 
"BMC Emerg Med"[Journal] OR 
"BMC Endocr Disord"[Journal] OR 
"BMC Fam Pract"[Journal] OR 
"BMC Gastroenterol"[Journal] OR 
"BMC Geriatr"[Journal] OR 
"BMC Health Serv Res"[Journal] OR 
"BMC Immunol"[Journal] OR 
"BMC Infect Dis"[Journal] OR 
"BMC Med Genet"[Journal] OR 
"BMC Med Imaging"[Journal] OR 
"BMC Med Inform Decis Mak"[Journal] OR 
"BMC Med"[Journal] OR 
"BMC Microbiol"[Journal] OR 
"BMC Mol Biol"[Journal] OR 
"BMC Musculoskelet Disord"[Journal] OR 
"BMC Nephrol"[Journal] OR 
"BMC Neurol"[Journal] OR 
"BMC Neurosci"[Journal] OR 
"BMC Nurs"[Journal] OR 
"BMC Ophthalmol"[Journal] OR 
"BMC Oral Health"[Journal] OR 
"BMC Palliat Care"[Journal] OR 
"BMC Pediatr"[Journal] OR 
"BMC Pharmacol"[Journal] OR 
"BMC Pregnancy Childbirth"[Journal] OR 
"BMC Proc"[Journal] OR 
"BMC Psychiatry"[Journal] OR 
"BMC Public Health"[Journal] OR 
"BMC Pulm Med"[Journal] OR 
"BMC Res Notes"[Journal] OR 
"BMC Surg"[Journal] OR 
"BMC Urol"[Journal] OR 
"BMC Womens Health"[Journal] OR 
"Brain"[Journal] OR 
"Brain Dev"[Journal] OR 
"Breast Cancer Res"[Journal] OR 
"Breast Cancer Res Treat"[Journal] OR 
"Br J Cancer"[Journal] OR 
"Br J Haematol"[Journal] OR 
"CA Cancer J Clin"[Journal] OR 
"Can Fam Physician"[Journal] OR 
"Can J Anaesth"[Journal] OR 
"Can J Microbiol"[Journal] OR 
"CMAJ"[Journal] OR 
"Cancer"[Journal] OR 
"Cancer Cell Int"[Journal] OR 
"Cancer Invest"[Journal] OR 
"Cancer J"[Journal] OR 
"Cancer Lett"[Journal] OR 
"Cancer Res"[Journal] OR 
"Cardiovasc Diabetol"[Journal] OR 
"Cardiovasc Ultrasound"[Journal] OR 
"Cell Commun Signal"[Journal] OR 
"Cephalalgia"[Journal] OR 
"Cerebrospinal Fluid Res"[Journal] OR 
"Chest"[Journal] OR 
"Child Adolesc Psychiatry Ment Health"[Journal] OR 
"Chiropr Osteopat"[Journal] OR 
"Chronic Dis Inj Can"[Journal] OR 
"Circulation"[Journal] OR 
"Circ Res"[Journal] OR 
"Circ Arrhythm Electrophysiol"[Journal] OR 
"Circ Cardiovasc Interv"[Journal] OR 
"Circ Cardiovasc Qual Outcomes"[Journal] OR 
"Circ Heart Fail"[Journal] OR 
"Cleve Clin J Med"[Journal] OR 
"Climacteric"[Journal] OR 
"Clin Mol Allergy"[Journal] OR 
"Clin Breast Cancer"[Journal] OR 
"Clin Cancer Res"[Journal] OR 
"Clin Colorectal Cancer"[Journal] OR 
"Clin Endocrinol (Oxf)"[Journal] OR 
"Clin Gastroenterol Hepatol"[Journal] OR 
"Clin Immunol"[Journal] OR 
"Clin Infect Dis"[Journal] OR 
"Clin J Am Soc Nephrol"[Journal] OR 
"Clin Nephrol"[Journal] OR 
"Clin Orthop Relat Res"[Journal] OR 
"Clin Pediatr [Phila]"[Journal] OR 
"Clin Pharmacol Ther"[Journal] OR 
"Clin Pract Epidemiol Ment Health"[Journal] OR 
"Clin Rehabil"[Journal] OR 
"Clin Rev Allergy Immunol"[Journal] OR 
"Clin Toxicol [Phila]"[Journal] OR 
"Comp Hepatol"[Journal] OR 
"Complement Ther Clin Pract"[Journal] OR 
"Complement Ther Med"[Journal] OR 
"Contraception"[Journal] OR 
"Cough"[Journal] OR 
"Crit Care"[Journal] OR 
"Crit Care Med"[Journal] OR 
"Curr Infect Dis Rep"[Journal] OR 
"Curr Opin Drug Discov Devel"[Journal] OR 
"Curr Opin Mol Ther"[Journal] OR 
"Cutis"[Journal] OR 
"Depress Anxiety"[Journal] OR 
"Dev Med Child Neurol"[Journal] OR 
"Diabetes"[Journal] OR 
"Diabetes Care"[Journal] OR 
"Diabetes Obes Metab"[Journal] OR 
"Diabet Med"[Journal] OR 
"Diabetologia"[Journal] OR 
"Diagn Pathol"[Journal] OR 
"Digestion"[Journal] OR 
"Dig Dis Sci"[Journal] OR 
"Dis Colon Rectum"[Journal] OR 
"Drug Alcohol Depend"[Journal] OR 
"Dyn Med"[Journal] OR 
"Emerg Med J"[Journal] OR 
"Med J Aust"[Journal] OR 
"Endocr Pract"[Journal] OR 
"Endocr Rev"[Journal] OR 
"Endocrinology"[Journal] OR 
"Endoscopy"[Journal] OR 
"Environ Health"[Journal] OR 
"Epidemiol Perspect Innov"[Journal] OR 
"Epilepsia"[Journal] OR 
"Epilepsy Behav"[Journal] OR 
"Eur Heart J"[Journal] OR 
"Eur J Cancer"[Journal] OR 
"Eur J Endocrinol"[Journal] OR 
"Eur J Pediatr"[Journal] OR 
"Evid Based Complement Alternat Med"[Journal] OR 
"Explore [NY]"[Journal] OR 
"Fam Pract"[Journal] OR 
"Fam Pract Manag"[Journal] OR 
"Fertil Steril"[Journal] OR 
"Fitoterapia"[Journal] OR 
"Foot Ankle Int"[Journal] OR 
"Gastroenterology"[Journal] OR 
"Gastrointest Endosc"[Journal] OR 
"Genet Vaccines Ther"[Journal] OR 
"Genet Med"[Journal] OR 
"Genome Biol"[Journal] OR 
"Geriatrics"[Journal] OR 
"Gut"[Journal] OR 
"Gut Pathog"[Journal] OR 
"Gynecol Oncol"[Journal] OR 
"Haematologica"[Journal] OR 
"Head Face Med"[Journal] OR 
"Head Neck"[Journal] OR 
"Head Neck Oncol"[Journal] OR 
"Headache"[Journal] OR 
"Health Qual Life Outcomes"[Journal] OR 
"Health Res Policy Syst"[Journal] OR 
"Heart Lung"[Journal] OR 
"Heart"[Journal] OR 
"Heart Rhythm"[Journal] OR 
"Hemodial Int"[Journal] OR 
"Hepatology"[Journal] OR 
"Hum Reprod"[Journal] OR 
"Hum Resour Health"[Journal] OR 
"Hypertension"[Journal] OR 
"IDrugs"[Journal] OR 
"Immun Ageing"[Journal] OR 
"Infect Immun"[Journal] OR 
"Infect Control Hosp Epidemiol"[Journal] OR 
"Infect Agent Cancer"[Journal] OR 
"Inflamm Bowel Dis"[Journal] OR 
"Integr Cancer Ther"[Journal] OR 
"Integr Med"[Journal] OR 
"Intensive Care Med"[Journal] OR 
"Intern Med"[Journal] OR 
"Int Arch Allergy Immunol"[Journal] OR 
"Int Arch Med"[Journal] OR 
"Int Breastfeed J"[Journal] OR 
"Int J Cancer"[Journal] OR 
"Int J Clin Pract"[Journal] OR 
"Int J Nurs Stud"[Journal] OR 
"Int J Obes"[Journal] OR 
"Int J Radiat Oncol Biol Phys"[Journal] OR 
"Int J Tuberc Lung Dis"[Journal] OR 
"Int J Urol"[Journal] OR 
"Int Semin Surg Oncol"[Journal] OR 
"Int Urogynecol J Pelvic Floor Dysfunct"[Journal] OR 
"JACC Cardiovasc Imaging"[Journal] OR 
"JACC Cardiovasc Interv"[Journal] OR 
"J Acquir Immune Defic Syndr"[Journal] OR 
"J Adolesc Health"[Journal] OR 
"J Adv Nurs"[Journal] OR 
"J Affect Disord"[Journal] OR 
"J Altern Complement Med"[Journal] OR 
"J Atten Disord"[Journal] OR 
"J Autoimmune Dis"[Journal] OR 
"J Bone Miner Res"[Journal] OR 
"J Brachial Plex Peripher Nerve Inj"[Journal] OR 
"J Cancer Res Clin Oncol"[Journal] OR 
"J Card Fail"[Journal] OR 
"J Cardiopulm Rehabil Prev"[Journal] OR 
"J Cardiothorac Surg"[Journal] OR 
"J Cardiovasc Electrophysiol"[Journal] OR 
"J Cardiovasc Magn Reson"[Journal] OR 
"J Child Neurol"[Journal] OR 
"J Circadian Rhythms"[Journal] OR 
"J Clin Gastroenterol"[Journal] OR 
"J Clin Microbiol"[Journal] OR 
"J Clin Nurs"[Journal] OR 
"J Clin Oncol"[Journal] OR 
"J Consult Clin Psychol"[Journal] OR 
"J Dent Res"[Journal] OR 
"J Dev Behav Pediatr"[Journal] OR 
"J Emerg Med"[Journal] OR 
"J Ethnopharmacol"[Journal] OR 
"J Fam Pract"[Journal] OR 
"J Foot Ankle Res"[Journal] OR 
"J Foot Ankle Surg"[Journal] OR 
"J Gen Intern Med"[Journal] OR 
"J Hematol Oncol"[Journal] OR 
"J Hepatol"[Journal] OR 
"J Herb Pharmacother"[Journal] OR 
"J Holist Nurs"[Journal] OR 
"J Hosp Med"[Journal] OR 
"J Hypertens"[Journal] OR 
"J Immune Based Ther Vaccines"[Journal] OR 
"J Immunol"[Journal] OR 
"J Infect"[Journal] OR 
"J Invest Dermatol"[Journal] OR 
"J Manipulative Physiol Ther"[Journal] OR 
"J Med Food"[Journal] OR 
"J Mol Signal"[Journal] OR 
"J Neuroeng Rehabil"[Journal] OR 
"J Neuroinflammation"[Journal] OR 
"J Neurol Neurosurg Psychiatry"[Journal] OR 
"J Neurooncol"[Journal] OR 
"J Neurosurg"[Journal] OR 
"J Occup Med Toxicol"[Journal] OR 
"J Orthop Surg Res"[Journal] OR 
"J Ovarian Res"[Journal] OR 
"J Pain Symptom Manage"[Journal] OR 
"J Palliat Med"[Journal] OR 
"J Pediatr Adolesc Gynecol"[Journal] OR 
"J Pediatr Endocrinol Metab"[Journal] OR 
"J Pediatr Gastroenterol Nutr"[Journal] OR 
"J Pediatr Orthop"[Journal] OR 
"J Pediatr Surg"[Journal] OR 
"J Perinatol"[Journal] OR 
"J Pers Disord"[Journal] OR 
"J Physiother"[Journal] OR 
"J Reprod Med"[Journal] OR 
"J Sleep Res"[Journal] OR 
"J Shoulder Elbow Surg"[Journal] OR 
"J Surg Oncol"[Journal] OR 
"J Am Acad Child Adolesc Psychiatry"[Journal] OR 
"J Am Acad Dermatol"[Journal] OR 
"J Am Coll Cardiol"[Journal] OR 
"J Am Coll Surg"[Journal] OR 
"J Am Diet Assoc"[Journal] OR 
"J Am Geriatr Soc"[Journal] OR 
"J Am Med Inform Assoc"[Journal] OR 
"J Am Podiatr Med Assoc"[Journal] OR 
"J Am Soc Echocardiogr"[Journal] OR 
"J Am Soc Nephrol"[Journal] OR 
"J Int AIDS Soc"[Journal] OR 
"J Int Soc Sports Nutr"[Journal] OR 
"J Natl Cancer Inst"[Journal] OR 
"J Natl Compr Canc Netw"[Journal] OR 
"J Soc Integr Oncol"[Journal] OR 
"J Thorac Oncol"[Journal] OR 
"J Thromb Haemost"[Journal] OR 
"J Toxicol Clin Toxicol"[Journal] OR 
"J Tradit Chin Med"[Journal] OR 
"J Trauma Manag Outcomes"[Journal] OR 
"J Vasc Surg"[Journal] OR 
"J Virol"[Journal] OR 
"Kidney Int"[Journal] OR 
"Laryngoscope"[Journal] OR 
"Leukemia"[Journal] OR 
"Leuk Lymphoma"[Journal] OR 
"Lipids Health Dis"[Journal] OR 
"Lung"[Journal] OR 
"Lung Cancer"[Journal] OR 
"Lupus"[Journal] OR 
"Malar J"[Journal] OR 
"Man Ther"[Journal] OR 
"Maturitas"[Journal] OR 
"Mayo Clin Proc"[Journal] OR 
"Med Care"[Journal] OR 
"Med Sci Sports Exerc"[Journal] OR 
"Menopause"[Journal] OR 
"Mol Brain"[Journal] OR 
"Mol Cancer"[Journal] OR 
"Mol Pain"[Journal] OR 
"Mov Disord"[Journal] OR 
"Muscle Nerve"[Journal] OR 
"Nat Prod Commun"[Journal] OR 
"Nephron Clin Pract"[Journal] OR 
"Neural Dev"[Journal] OR 
"Neurology"[Journal] OR 
"Nutr Metab [Lond]"[Journal] OR 
"Nutr J"[Journal] OR 
"Obesity [Silver Spring]"[Journal] OR 
"Obstet Gynecol Surv"[Journal] OR 
"Oncology"[Journal] OR 
"Ophthalmology"[Journal] OR 
"Orphanet J Rare Dis"[Journal] OR 
"Osteopath Med Prim Care"[Journal] OR 
"Osteoporos Int"[Journal] OR 
"Otolaryngol Head Neck Surg"[Journal] OR 
"Pain"[Journal] OR 
"Palliat Med"[Journal] OR 
"Pediatr Allergy Immunol"[Journal] OR 
"Pediatr Cardiol"[Journal] OR 
"Pediatr Dermatol"[Journal] OR 
"Pediatr Emerg Care"[Journal] OR 
"Pediatr Hematol Oncol"[Journal] OR 
"Pediatr Nephrol"[Journal] OR 
"Pediatr Neurol"[Journal] OR 
"Pediatr Pulmonol"[Journal] OR 
"Pediatr Radiol"[Journal] OR 
"Pediatr Res"[Journal] OR 
"Percept Mot Skills"[Journal] OR 
"Perit Dial Int"[Journal] OR 
"Pharmacotherapy"[Journal] OR 
"Phys Ther"[Journal] OR 
"Phytomedicine"[Journal] OR 
"Phytother Res"[Journal] OR 
"Planta Med"[Journal] OR 
"Prenat Diagn"[Journal] OR 
"Preventing Chronic Disease"[Journal] OR
"Prev Med"[Journal] OR 
"Prog Neuropsychopharmacol Biol Psychiatry"[Journal] OR 
"Prostate"[Journal] OR 
"Psychosom Med"[Journal] OR 
"Psychosomatics"[Journal] OR 
"Psychother Psychosom"[Journal] OR 
"PLoS Med"[Journal] OR 
"PLoS Negl Trop Dis"[Journal] OR 
"PLoS One"[Journal] OR 
"QJM"[Journal] OR 
"Radiat Oncol"[Journal] OR 
"Radiology"[Journal] OR 
"Radiother Oncol"[Journal] OR 
"Reprod Biol Endocrinol"[Journal] OR 
"Reprod Health"[Journal] OR 
"Res Nurs Health"[Journal] OR 
"Respiration"[Journal] OR 
"Respir Med"[Journal] OR 
"Respir Res"[Journal] OR 
"Retrovirology"[Journal] OR 
"Rev Panam Salud Publica"[Journal] OR 
"Rheumatology [Oxford, England]"[Journal] OR 
"Rhinology"[Journal] OR 
"Scand J Infect Dis"[Journal] OR 
"Scand J Trauma Resusc Emerg Med"[Journal] OR 
"Schizophr Bull"[Journal] OR 
"Scoliosis"[Journal] OR 
"Shock"[Journal] OR 
"Singapore Med J"[Journal] OR 
"Sleep"[Journal] OR 
"Spine"[Journal] OR 
"Stroke"[Journal] OR 
"Subst Abuse Treat Prev Policy"[Journal] OR 
"Surgery"[Journal] OR 
"Ann Thorac Surg"[Journal] OR 
"Aust N Z J Obstet Gynaecol"[Journal] OR 
"Aust J Holist Nurs"[Journal] OR 
"Br J Dermatol"[Journal] OR 
"Br J Gen Pract"[Journal] OR 
"Br J Ophthalmol"[Journal] OR 
"Br J Psychiatry"[Journal] OR 
"Br J Radiol"[Journal] OR 
"Br J Surg"[Journal] OR 
"Clin J Pain"[Journal] OR 
"Eur Respir J"[Journal] OR 
"Int J Behav Nutr Phys Act"[Journal] OR 
"Int J Eat Disord"[Journal] OR 
"J Allergy Clin Immunol"[Journal] OR 
"J Antimicrob Chemother"[Journal] OR 
"J Arthroplasty"[Journal] OR 
"J Bone Joint Surg Am"[Journal] OR 
"J Bone Joint Surg Br"[Journal] OR 
"J Clin Endocrinol Metab"[Journal] OR 
"J Clin Psychiatry"[Journal] OR 
"J Hand Surg Eur Vol"[Journal] OR 
"J Infect Dis"[Journal] OR 
"J Man Manip Ther"[Journal] OR 
"J Orthop Sports Phys Ther"[Journal] OR 
"J Pain"[Journal] OR 
"J Pediatr"[Journal] OR 
"J Rheumatol"[Journal] OR 
"J Am Osteopath Assoc"[Journal] OR 
"J Thorac Cardiovasc Surg"[Journal] OR 
"J Trauma"[Journal] OR 
"J Urol"[Journal] OR 
"Lancet Infect Dis"[Journal] OR 
"Lancet Neurol"[Journal] OR 
"Lancet Oncol"[Journal] OR 
"Oncologist"[Journal] OR 
"Pediatr Infect Dis J"[Journal] OR 
"Thorax"[Journal] OR 
"Thromb Haemost"[Journal] OR 
"Thromb J"[Journal] OR 
"Thyroid"[Journal] OR 
"Thyroid Res"[Journal] OR 
"Tob Induc Dis"[Journal] OR 
"Trans R Soc Trop Med Hyg"[Journal] OR 
"Transplantation"[Journal] OR 
"Transpl Infect Dis"[Journal] OR 
"Ultrasound Obstet Gynecol"[Journal] OR 
"Urology"[Journal] OR 
"Vasc Med"[Journal] OR 
"Wilderness Environ Med"[Journal] OR 
"World J Emerg Surg"[Journal] OR 
"World J Surg Oncol"[Journal] OR 
"Acad Emerg Med"[Journal] OR 
"Acta Orthop"[Journal] OR 
"Age Ageing"[Journal] OR 
"Am J Cardiol"[Journal] OR 
"Am J Epidemiol"[Journal] OR 
"Am J Gastroenterol"[Journal] OR 
"Am J Kidney Dis"[Journal] OR 
"Am J Med"[Journal] OR 
"Am J Obstet Gynecol"[Journal] OR 
"Am J Prev Med"[Journal] OR 
"Am J Psychiatry"[Journal] OR 
"Am J Public Health"[Journal] OR 
"Am J Respir Crit Care Med"[Journal] OR 
"Am J Sports Med"[Journal] OR 
"Anesth Analg"[Journal] OR 
"Anesthesiology"[Journal] OR 
"Ann Emerg Med"[Journal] OR 
"Ann Fam Med"[Journal] OR 
"Ann Intern Med"[Journal] OR 
"Ann Neurol"[Journal] OR 
"Ann Rheum Dis"[Journal] OR 
"Ann Surg"[Journal] OR 
"Ann Thorac Surg"[Journal] OR 
"Appl Nurs Res"[Journal] OR 
"Arch Dis Child"[Journal] OR 
"Arch Dis Child Fetal Neonatal Ed"[Journal] OR 
"Arch Gen Psychiatry"[Journal] OR 
"Arch Intern Med"[Journal] OR 
"Arch Pediatr Adolesc Med"[Journal] OR 
"Arch Phys Med Rehabil"[Journal] OR 
"Arch Surg"[Journal] OR 
"Arthritis Care Res (Hoboken)"[Journal] OR 
"Arthritis Rheum"[Journal] OR 
"Arthroscopy"[Journal] OR 
"BJOG"[Journal] OR 
"BMJ"[Journal] OR 
"Br J Gen Pract"[Journal] OR 
"Br J Psychiatry"[Journal] OR 
"Br J Surg"[Journal] OR 
"Can J Anaesth"[Journal] OR 
"CADTH Technol Overv"[Journal] OR 
"Cancer Nurs"[Journal] OR 
"Chest"[Journal] OR 
"Circulation"[Journal] OR 
"Clin J Am Soc Nephrol"[Journal] OR 
"Clin J Pain"[Journal] OR 
"Clin Orthop Relat Res"[Journal] OR 
"Clin Pharmacol Ther"[Journal] OR 
"Clin Rehabil"[Journal] OR 
"CMAJ"[Journal] OR 
"Cochrane Database Syst Rev"[Journal] OR 
"Crit Care Med"[Journal] OR 
"Diabet Med"[Journal] OR 
"Diabetes Care"[Journal] OR 
"Diabetes Obes Metab"[Journal] OR 
"Eur Heart J"[Journal] OR 
"Eur Respir J"[Journal] OR 
"Evid Rep Technol Assess (Full Rep)"[Journal] OR 
"Fam Pract"[Journal] OR 
"Foot Ankle Int"[Journal] OR 
"Gastroenterology"[Journal] OR 
"Gut"[Journal] OR 
"Headache"[Journal] OR 
"Health Technol Assess"[Journal] OR 
"Heart"[Journal] OR 
"Implement Sci"[Journal] OR 
"Int J Clin Pract"[Journal] OR 
"Int J Nurs Stud"[Journal] OR 
"Int J Obes (Lond)"[Journal] OR 
"J Adv Nurs"[Journal] OR 
"J Allergy Clin Immunol"[Journal] OR 
"J Am Coll Cardiol"[Journal] OR 
"J Am Coll Surg"[Journal] OR 
"J Am Geriatr Soc"[Journal] OR 
"J Am Soc Nephrol"[Journal] OR 
"J Arthroplasty"[Journal] OR 
"J Bone Joint Surg Am"[Journal] OR 
"J Bone Joint Surg Br"[Journal] OR 
"J Clin Nurs"[Journal] OR 
"J Clin Oncol"[Journal] OR 
"J Clin Pharmacol"[Journal] OR 
"J Gen Intern Med"[Journal] OR 
"J Infect Dis"[Journal] OR 
"J Neurol Neurosurg Psychiatry"[Journal] OR 
"J Neurosurg"[Journal] OR 
"J Nurs Scholarsh"[Journal] OR 
"J Orthop Sports Phys Ther"[Journal] OR 
"J Orthop Trauma"[Journal] OR 
"J Pain"[Journal] OR 
"J Pediatr"[Journal] OR 
"J Pediatr Orthop"[Journal] OR 
"J Physiother"[Journal] OR 
"J Rheumatol"[Journal] OR 
"J Shoulder Elbow Surg"[Journal] OR 
"J Surg Oncol"[Journal] OR 
"J Thorac Cardiovasc Surg"[Journal] OR 
"J Trauma"[Journal] OR 
"J Vasc Surg"[Journal] OR 
"JAMA"[Journal] OR 
"Kidney Int"[Journal] OR 
"Lancet"[Journal] OR 
"Lancet Neurol"[Journal] OR 
"Lancet Oncol"[Journal] OR 
"Mayo Clin Proc"[Journal] OR 
"Med Care"[Journal] OR 
"N Engl J Med"[Journal] OR 
"Neurology"[Journal] OR 
"Nurs Res"[Journal] OR 
"Obesity (Silver Spring)"[Journal] OR 
"Obstet Gynecol"[Journal] OR 
"Pain"[Journal] OR 
"Pediatrics"[Journal] OR 
"Pharmacotherapy"[Journal] OR 
"Phys Ther"[Journal] OR 
"Prev Med"[Journal] OR 
"Res Nurs Health"[Journal] OR 
"Rheumatology (Oxford)"[Journal] OR 
"Spine (Phila Pa 1976)"[Journal] OR 
"Spine J"[Journal] OR 
"Stroke"[Journal] OR 
"Thorax"[Journal] OR 
"Transplantation"[Journal] OR 
"West J Nurs Res"[Journal] OR 
"World J Surg"[Journal]
)
END_OF_STRING;

$query = str_replace("\n", " ", $query);

pubmed_fetch($query);

?>



