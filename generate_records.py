#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Generator for notification records 31-100.
Each entry is expanded into a 500+ word HTML full_content block and a valid
INSERT value tuple. Facts (org, eligibility, fees, age, official sites) are
real; the descriptive prose is templated per status type.
"""

def esc(s):
    if s is None:
        return "NULL"
    return "'" + str(s).replace("'", "''") + "'"

def num(n):
    return "NULL" if n is None else str(n)

def dec(n):
    return "NULL" if n is None else f"{n:.2f}"

# Status presets -> (status_enum, computed_status, post_type)
PRESET = {
    "JOB":    ("active", "ACTIVE", "notification"),
    "ADMIT":  ("admitcard", "ADMIT_CARD", "admitcard"),
    "RESULT": ("result", "RESULT", "result"),
    "ANSWER": ("active", "ANSWER_KEY", "notification"),
}

def build_content(r):
    t = r["type"]
    org = r["org"]
    post = r.get("post") or r["name"].split(" – ")[0].split(" - ")[0]
    qual = r["qual"]
    elig = r["elig"]
    age = r["age"]
    sel = r["sel"]
    extra = r.get("extra", "")
    name = r["name"]
    site = r["site"]
    p = []
    if t == "JOB":
        p.append(f"<h2>{name} – Complete Recruitment Overview</h2>")
        p.append(f"<p>The {org} has released the official notification for the recruitment to the post of <strong>{post}</strong>. This recruitment is a valuable opportunity for eligible candidates across the country to secure a stable and respectable government career with attractive pay, allowances, job security and excellent avenues for professional growth. Interested candidates who fulfil the prescribed eligibility conditions are advised to read the complete official notification carefully before submitting their online application on the official website {site}.</p>")
        p.append(f"<h3>About the Post & Organisation</h3><p>The {org} conducts this recruitment to fill the post of {post} through a transparent and merit-based selection process. {extra} The selected candidates will be entitled to the pay scale prescribed under the applicable pay matrix along with dearness allowance, house rent allowance, transport allowance and other admissible benefits as per the rules of the organisation. The post offers a structured career path with opportunities for promotion to higher grades based on seniority and departmental examinations.</p>")
        p.append("<h3>Important Dates</h3><ul>" + "".join(f"<li>{d}</li>" for d in r["dates"]) + "</ul>")
        p.append(f"<h3>Eligibility & Educational Qualification</h3><p>To be eligible for this recruitment, candidates must satisfy the educational qualification requirement, which is as follows: {qual}. {elig} Candidates are strongly advised to verify that they meet all the prescribed academic and eligibility conditions as on the cut-off date mentioned in the official notification, since applications that do not meet the requirements are liable to be rejected at any stage of the recruitment process.</p>")
        p.append(f"<h3>Age Limit & Relaxation</h3><p>The age limit prescribed for this recruitment is {age}. Age relaxation is provided to candidates belonging to reserved categories as per the rules of the Government and the organisation, namely five years for SC and ST candidates, three years for OBC candidates, ten years for Persons with Benchmark Disabilities, and additional relaxation for Ex-Servicemen and other special categories as specified in the detailed notification.</p>")
        p.append(f"<h3>Application Fee</h3><p>The prescribed application fee must be paid online through net banking, debit card, credit card, UPI or other digital payment modes available on the portal. Candidates belonging to SC, ST, Persons with Disabilities, Ex-Servicemen and, in many cases, women candidates are eligible for fee exemption or a reduced fee as per the category-wise fee structure mentioned in the notification. Candidates should ensure that the fee is paid within the prescribed deadline, as applications without successful fee payment are treated as incomplete.</p>")
        p.append(f"<h3>Selection Process</h3><p>The selection process for this recruitment consists of {sel} The final merit list is prepared strictly on the basis of the candidate''s performance in the prescribed stages, subject to document verification and fulfilment of all eligibility conditions. Candidates are advised to prepare thoroughly for each stage and to keep track of the official announcements regarding the examination schedule and results.</p>")
        p.append(f"<h3>How to Apply</h3><p>Eligible and interested candidates must apply online through the official website {site} within the application window. The step-by-step procedure involves registering with a valid email address and mobile number, filling in the personal, educational and communication details, uploading the scanned photograph, signature and required documents in the prescribed format and size, paying the application fee, and finally submitting the completed application form. Candidates are advised to take a printout of the submitted application form and the fee receipt for future reference, and to avoid waiting until the last date to prevent any last-minute technical difficulties.</p>")
    elif t == "ADMIT":
        p.append(f"<h2>{name}</h2>")
        p.append(f"<p>The {org} has released the <strong>admit card</strong> for the {post} examination on its official website {site}. The admit card, also known as the call letter or hall ticket, is one of the most important documents that every candidate must carry to the examination centre, since no candidate is permitted to enter the examination hall without a valid printed admit card and a matching original photo identity proof. Candidates who successfully submitted their applications can now download their admit card by logging in with their credentials.</p>")
        p.append(f"<h3>About the Examination</h3><p>This examination is conducted by the {org} as part of the selection process for the post of {post}. {extra} The admit card contains essential information such as the candidate''s name, roll number, registration number, photograph, signature, examination date, reporting time, shift, and the complete address of the allotted examination centre, along with detailed instructions that every candidate must read and follow carefully on the day of the examination.</p>")
        p.append(f"<h3>How to Download the Admit Card</h3><ol><li>Visit the official website {site}.</li><li>Click on the admit card or call letter download link for {post}.</li><li>Log in using your registration number, roll number, date of birth or password as applicable.</li><li>The admit card will be displayed on the screen with all your examination details.</li><li>Verify the details, download the admit card and take a clear printout on A4 size paper.</li></ol>")
        p.append("<h3>Documents to Carry</h3><p>Candidates must carry the printed copy of the admit card along with at least one original valid photo identity proof such as Aadhaar Card, Voter ID, PAN Card, Driving Licence or Passport. A recent passport-size colour photograph identical to the one uploaded during the application must also be carried and affixed where required. Candidates who fail to bring these documents will not be allowed to appear in the examination under any circumstances.</p>")
        p.append("<h3>Important Instructions</h3><p>Candidates are advised to reach the examination centre well before the reporting time indicated on the admit card, as late entry is strictly prohibited. Biometric verification, frisking and document checks are carried out at the centre and may take considerable time. Electronic gadgets including mobile phones, calculators, smartwatches, Bluetooth devices, and any printed or written study material are strictly banned inside the examination hall. Candidates must carefully verify every detail printed on the admit card and immediately report any discrepancy to the conducting authority.</p>")
        p.append(f"<h3>Eligibility Recap</h3><p>This examination is open to candidates who meet the prescribed qualification, namely {qual}. {elig} The age criterion for the post is {age}. Candidates appearing in the examination should ensure that they continue to satisfy all eligibility conditions, as these are verified during the document verification stage that follows the written examination.</p>")
        p.append(f"<h3>Selection Process & Next Steps</h3><p>The overall selection for this recruitment consists of {sel} After appearing in the examination for which this admit card is issued, candidates should regularly monitor the official website {site} for the answer key, result and the schedule of subsequent stages. It is advisable to retain the admit card safely even after the examination, as it is often required at later stages such as document verification and counselling.</p>")
    elif t == "RESULT":
        p.append(f"<h2>{name}</h2>")
        p.append(f"<p>The {org} has declared the <strong>result</strong> of the {post} examination on its official website {site}. Candidates who appeared in the examination can now check their qualifying status, marks and the category-wise cut off by logging in to the candidate portal or by checking the list of selected roll numbers published in the result notice. This result determines the candidates who are eligible to proceed to the next stage of the recruitment process.</p>")
        p.append(f"<h3>About the Recruitment</h3><p>This recruitment is conducted by the {org} for the post of {post}. {extra} The result is processed on the basis of the marks obtained by the candidates, after applying normalisation where the examination is held in multiple shifts, and the category-wise cut off marks determined by the conducting authority based on the number of vacancies, the number of candidates and the overall difficulty of the examination.</p>")
        p.append(f"<h3>How to Check the Result</h3><ol><li>Visit the official website {site}.</li><li>Click on the result link for the {post} examination.</li><li>A PDF containing the roll numbers of qualified or selected candidates will open.</li><li>Search for your roll number using the find option.</li><li>Log in with your registration number and date of birth to view your individual scorecard with detailed marks.</li></ol>")
        p.append("<h3>Cut Off Marks & Scorecard</h3><p>The cut off marks are released separately for each category, namely Unreserved, EWS, OBC, SC, ST and other applicable sub-categories. The individual scorecard displays the candidate''s name, roll number, category, sectional and total marks, normalised marks where applicable, and the final qualifying status. Candidates are advised to download and preserve their scorecard, as it serves as an important record and may be required at later stages of the recruitment.</p>")
        p.append(f"<h3>Next Steps for Qualified Candidates</h3><p>Candidates who have qualified must prepare for the subsequent stages of the selection process. The overall recruitment consists of {sel} Qualified candidates should keep their educational certificates, category certificate, identity proof and recent photographs ready for document verification, and should regularly check the official website for the schedule of the next stage. The final merit and appointment are subject to successful completion of all remaining stages.</p>")
        p.append(f"<h3>Eligibility Recap</h3><p>This recruitment is open to candidates fulfilling the qualification of {qual}. {elig} The prescribed age limit is {age}. Candidates should note that meeting the eligibility conditions is mandatory and will be strictly verified before final selection.</p>")
        p.append(f"<h3>Important Note</h3><p>Candidates should rely solely on the official website {site} for authentic result-related information and must beware of fraudulent agents or unofficial websites that claim to influence results. Any grievance regarding the result should be addressed to the {org} through the prescribed official channel within the timeframe specified in the result notice, after which representations are generally not entertained.</p>")
    else:  # ANSWER
        p.append(f"<h2>{name}</h2>")
        p.append(f"<p>The {org} has released the <strong>provisional answer key</strong> for the {post} examination on its official website {site}, along with the question paper and the candidate''s recorded responses. This facility allows every candidate who appeared in the examination to verify their marked answers against the answers considered correct by the conducting authority, to estimate their probable score, and to raise objections against any answer they believe to be incorrect before the final result is processed.</p>")
        p.append(f"<h3>About the Examination</h3><p>This examination is conducted by the {org} for the post of {post}. {extra} The release of the provisional answer key is a transparent step in the evaluation process, as the final result is prepared only after considering all valid objections raised by the candidates during the prescribed window. The answer key facility is available through the candidate login for a limited number of days, usually three to five days.</p>")
        p.append(f"<h3>How to Download the Answer Key</h3><ol><li>Visit the official website {site}.</li><li>Click on the answer key or response sheet link for the {post} examination.</li><li>Log in using your registration number, roll number, date of birth or password.</li><li>View your question paper, your marked responses and the provisional answer key.</li><li>Download and save a copy for calculating your score and preparing objections if required.</li></ol>")
        p.append("<h3>How to Raise Objections</h3><p>Candidates who are not satisfied with any answer in the provisional answer key can raise objections online during the specified window. A prescribed fee is charged for each question or answer challenged, which is refunded if the objection is found valid after review by a committee of subject experts. Candidates must provide proper justification and authentic references in support of each objection. Objections submitted after the deadline or through any mode other than the official online portal are not entertained under any circumstances.</p>")
        p.append("<h3>Importance of Reviewing the Answer Key</h3><p>Reviewing the answer key is crucial because the final result is prepared on the basis of the final answer key, which is published after evaluating all the objections received. By comparing their recorded responses with the provisional key, candidates can estimate their score using the applicable marking scheme, including the negative marking provisions, and assess their chances of qualifying for the next stage. This also helps candidates plan ahead for the subsequent stages of the recruitment.</p>")
        p.append(f"<h3>Eligibility Recap</h3><p>This examination is open to candidates fulfilling the qualification of {qual}. {elig} The prescribed age limit is {age}. The overall selection process consists of {sel}</p>")
        p.append(f"<h3>After the Objection Window</h3><p>Once the objection window closes, the subject experts of the {org} examine all the objections submitted by the candidates and prepare the final answer key. The result is then declared on the basis of this final key. Candidates are advised to regularly check the official website {site} for the final answer key, result and the schedule of the next stage of the recruitment.</p>")
    return "".join(p)


def build_tuple(r):
    status_enum, computed, post_type = PRESET[r["type"]]
    content = build_content(r)
    cols = [
        "NULL",
        esc(r["name"]),
        esc(r["slug"]),
        esc(r["short"]),
        esc(content),
        esc(r["category"]),
        esc(r["body"]),
        esc(r["org"]),
        esc(post_type),
        esc(r.get("notif_date")),
        esc(r.get("pub_date")),
        esc(r.get("app_start")),
        esc(r.get("last_apply")),
        esc(r.get("app_last")),
        esc(r.get("exam_date")),
        esc(r.get("admit_date")),
        esc(r.get("result_date")),
        esc("2026-06-19 10:00:00"),
        num(r.get("vac")),
        esc(r["age"]),
        esc(r["qual"]),
        esc(r["elig"]),
        dec(r.get("fee_gen")),
        dec(r.get("fee_obc")),
        dec(r.get("fee_sc")),
        esc(r["url"]),
        esc(r["site"]),
        esc(r.get("pdf")),
        esc(r.get("apply")),
        "NULL",
        "NULL",
        esc(status_enum),
        esc(computed),
        str(r.get("feat", 0)),
        "1",
        esc(r["seo"]),
        esc(r["meta"]),
        "NULL",
        "0",
        "NULL",
        "NULL",
        "CURRENT_TIMESTAMP",
        "CURRENT_TIMESTAMP",
    ]
    return "(" + ", ".join(cols) + ")"


from records_data import RECORDS

out = []
start_id = 31
for idx, r in enumerate(RECORDS):
    comment = f"-- Record {start_id + idx}: {r['type']}"
    out.append(comment + "\n" + build_tuple(r) + ",")

# verify word counts
import re
problems = []
for idx, r in enumerate(RECORDS):
    c = build_content(r)
    wc = len(re.sub(r'<[^>]+>', ' ', c).split())
    if wc < 500:
        problems.append((start_id + idx, r['name'], wc))

with open("generated_part.sql", "w") as f:
    f.write("\n".join(out) + "\n")

print(f"Generated {len(RECORDS)} records (IDs {start_id}-{start_id+len(RECORDS)-1}).")
if problems:
    print("UNDER 500 WORDS:")
    for pid, nm, wc in problems:
        print(f"  Record {pid}: {wc} words - {nm}")
else:
    print("All generated records are 500+ words.")
