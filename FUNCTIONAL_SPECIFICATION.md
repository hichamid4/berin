# BreIn Functional Specification

**Status:** Approved functional specification; database decisions approved. Stages 1–5 are implemented, including the offer-price filter. In Stage 6, About Us, Contact Us and private administrator access with mandatory second-factor authentication are implemented; the release review, API and deployment are not started. A Flutter mobile phase is approved for later (Section 11.8). Current status: Section 11.4.
**Source of functional requirements:** *Mémoire de Projet de Fin de Cycle — Web Application: BreIn*, Hicham ID AHMED, academic year 2022–2023 (43 pages).  
**Purpose:** Consolidate the original report into a traceable specification. Report statements, interpretations, and technical proposals are labeled separately.

## 1. Reading guide and source limits

- **[Report]** means stated in report prose, shown in a figure, or both.
- **[Proposal]** means a technical organization suggested here; it is not an original requirement.
- **[Decision]** means a user-confirmed product or schema decision that resolves, narrows, or excludes an item in the report.
- **[Implemented]** means present in the repository and verified as of the status noted in Section 11.4.
- **[Pending]** means approved for v1 but not yet implemented.
- **[Open]** means a report detail that remains unresolved and is not silently decided by this specification.

The report includes prose, a requirements/module diagram, a use-case diagram, a class diagram, two sequence diagrams, and screenshots/descriptions of some existing pages. It does **not** include database DDL, field-level validation rules, API contracts, or complete textual use-case specifications. Cardinalities and attributes below are transcribed from the class diagram where legible; their implementation meaning must be confirmed where unclear.

## 2. Product summary

**[Report]** BreIn is a responsive web application for connecting homeowners seeking home-improvement/repair services with handymen. Handymen state their skills; homeowners discover services and create requests; handymen submit offers; homeowners accept or reject offers; direct communication follows acceptance. The report names homeowners, handymen, and administrators as actors.

**[Report]** The documented UI includes login, account signup, services, request creation, a homeowner dashboard, About Us, and Contact Us. The report names Laravel, PHP, Bootstrap, HTML, CSS, and JavaScript as the original implementation stack. These historical choices do not mandate the rebuilt stack.

## 3. Functional requirements

IDs below are assigned for traceability; they are not identifiers from the report.

### 3.1 Accounts and access

| ID | Requirement | Source/evidence | Status |
|---|---|---|---|
| ACC-01 | A user can register as either a Homeowner or Handyman. | Brief, signup page description, use-case diagram | Explicit |
| ACC-02 | Signup collects first name, last name, username, password (minimum 8 characters), email, required phone (E.164, WhatsApp), account type, and optional profile city/address. | Account-module workflow; approved profile default | Explicit; Stage 1 registration implemented for identity and profile fields |
| ACC-03 | Handyman profile specifies skills. | Brief; class diagram Skills | Explicit; schema implemented, skill management pending |
| ACC-04 | Validate submitted registration data; save if valid and return to signup if invalid. | Account-module workflow | Explicit; validation rules unspecified |
| ACC-05 | Confirm account creation and send an email notification. | Account-module workflow | Explicit |
| ACC-06 | Activate an account through a link in email; update account verification state after a valid signed link. | Account-module workflow; approved email verification | Explicit; implemented and feature-tested |
| ACC-07 | Users can log in with email and password. | Login page description | Explicit; role-specific Stage 1 login implemented and feature-tested |
| ACC-08 | Users can log out. | User class; screenshot description | Explicit; implemented and feature-tested |
| ACC-09 | A user can edit account/profile information. | Account module; homeowner dashboard | Explicit |
| ACC-10 | A user can change/update their password. | Account module | Explicit |
| ACC-11 | Account closure preserves historical marketplace records by anonymizing the account; clear unique email and username and mark the identity anonymized. | Account module; approved database default | Decision: behavior approved; self-service account-closure UI is pending |
| ACC-12 | The user diagram includes “Forgot Password.” | Use-case diagram | Explicit; included in v1 and implemented/tested in Stage 1 |
| ACC-13 | “Activate account” is labeled “2FA” in the module/organigram. | Account module and organigram | Decision: email verification is the activation method for homeowners and handymen; 2FA is not used for them in v1. Administrators require an authenticator-app second factor (Section 11.7) |
| ACC-14 | Administrator authenticates to a protected admin account/area. | Admin actor description and diagram; project brief supplied by user | Decision: separate protected admin login under a private, environment-configured path, with mandatory authenticator-app second factor; provision via trusted Artisan command, no public admin signup or public link (Section 11.7) |

### 3.2 Catalog and discovery

| ID | Requirement | Source/evidence | Status |
|---|---|---|---|
| CAT-01 | Homeowner can browse/consult the service catalog and category list. | Brief, use-case diagram, class diagram | Explicit |
| CAT-02 | Homeowner can search services by category. | Brief, use-case diagram, services page description | Explicit |
| CAT-03 | Homeowner can search by service and price. | Use-case diagram; confirmed price-filter rule | Decision: price filtering uses submitted offer prices; filter UI/range pending |
| CAT-04 | Homeowner can view/list handymen. | Use-case diagram and organigram | Explicit in diagram |
| CAT-05 | Homeowner can search handymen by rating, location, or likes/favorites. | Use-case diagram; confirmed favorites decision | Decision: likes mean removable favorites; rating aggregation and precise location filters remain implementation detail |
| CAT-06 | Handyman can filter homeowner requests by category, service, and city/location. | Use-case diagram; actor prose says filtering is future work | Decision: included in v1; pending implementation |
| CAT-07 | Handyman sees only requests matching their skills and the approved service-to-skill mapping. | Project brief; approved schema decision | Decision: server-side matching; pending implementation |

### 3.3 Requests and offers

| ID | Requirement | Source/evidence | Status |
|---|---|---|---|
| REQ-01 | Homeowner creates a service request after selecting a category/service. | Brief, request sequence, page descriptions | Explicit |
| REQ-02 | Request contains description, desired work start date, and a private service location/city separate from profile address. | Request page; homeowner use-case prose; approved privacy rule | Explicit; approved columns are in the schema |
| REQ-03 | Request can include one or more photos; photos are optional. | Request page, sequence, class diagram PictureOff | Explicit |
| REQ-04 | Request sequence: list categories → list services for category → choose service → create request → optionally add pictures. | Sequence diagram | Explicit |
| REQ-05 | Handyman views/list requests and request details. | Handyman use-case diagram and offer sequence | Explicit |
| REQ-06 | Handyman submits an offer in response to a request. | Brief, class diagram, offer sequence | Explicit |
| REQ-07 | Offer contains positive price in MAD, proposed start/end dates, and description. | Class diagram Offer; approved defaults | Decision: dates are required and end date cannot precede start date |
| REQ-08 | Offer may include pictures. | Offer sequence and PictureOff class | Explicit in diagram |
| REQ-09 | Homeowner can review received offers and accept or refuse an offer. | Brief and homeowner actor prose | Explicit |
| REQ-10 | The system supports offer/request statuses to show submitted, accepted, refused, withdrawn, or superseded offers and request progress. | Homeowner dashboard description; approved state defaults | Decision: explicit lifecycle recorded in Section 11.3; pending workflow implementation |
| REQ-11 | Handyman can create a request for a service they offer. | Handyman actor prose | Decision: excluded; only homeowners create service requests |
| REQ-12 | Handyman can accept or decline requests. | Handyman prose and use-case diagram | Decision: excluded as a conflicting interpretation; handymen submit offers, and homeowners accept/refuse offers |
| REQ-13 | Handyman can view matching requests and manage their own submitted offers. | Use-case diagram reconciled with the approved workflow | Decision: pending implementation; handyman cannot accept/decline a homeowner request |

### 3.4 Communication, appointments, reviews, and payment

| ID | Requirement | Source/evidence | Status |
|---|---|---|---|
| COM-01 | After an offer is accepted, the homeowner can contact the selected handyman through WhatsApp. | Brief and homeowner use case; user-confirmed channel | Decision: WhatsApp only, post-acceptance; pending implementation |
| COM-02 | Homeowner can contact handyman via phone number. | Dashboard description | Decision: number exposure/action only for the accepted offer participants; exact display/link UX pending |
| COM-03 | Homeowner use-case says contact is via WhatsApp after booking. | Actor description | Decision: confirmed as the sole communication channel after acceptance |
| COM-04 | Use-case/organigram includes messaging, instant messages, sharing pictures/documents, and scheduling appointments. | Use-case diagram and organigram | Decision: internal messaging, document sharing, and appointment scheduling are excluded from the v1 website. In-app messaging linked to accepted offers is approved for the future mobile app only (Section 11.8) |
| COM-05 | Homeowner and handyman can schedule, accept, decline, and reschedule appointments. | Use-case diagram and class methods | Decision: excluded from v1 |
| REV-01 | Homeowner can review/rate a handyman after an accepted offer's request is completed and the homeowner owns that request. | Brief, use-case diagram, class diagram Reviews; approved review rule | Decision: one review per offer; transaction and server authorization required; pending implementation |
| REV-02 | Review includes a required 1–5 rating and optional comment. A homeowner favorite/like is a separate relation to a handyman. | Use-case and class diagrams; confirmed likes meaning | Decision: review likes/dislikes are excluded; homeowner favorites included |
| REV-03 | Administrator can monitor reviews. | Brief | Explicit; moderation actions/criteria unspecified |
| PAY-01 | Payment is represented in the use-case diagram for Handyman, with Cash and Online variants; Online shows Credit Card and PayPal. | Use-case diagram and organigram | Decision: all online/offline payment functionality is excluded from v1 |

### 3.5 Administration and public/contact pages

| ID | Requirement | Source/evidence | Status |
|---|---|---|---|
| ADM-01 | Administrator can view an overview/list of users and user details. | Admin use-case diagram, class diagram | Explicit |
| ADM-02 | Administrator can block and unblock users. | Admin use-case diagram and class diagram | Explicit |
| ADM-03 | Administrator can list/view services and categories. | Admin prose and diagram | Explicit |
| ADM-04 | Administrator can add, edit, and delete services. | Brief and admin prose/diagram | Explicit |
| ADM-05 | Administrator can add, edit, and delete categories. | Brief and admin prose/diagram | Explicit |
| ADM-06 | Administrator can manage skills. | Brief | Explicit; skill CRUD operations not enumerated |
| ADM-07 | Administrator can monitor reviews. | Brief | Explicit; moderation operations unspecified |
| PUB-01 | The site provides About Us information. | Realization chapter/page screenshot | Explicit page; content requirements unspecified |
| PUB-02 | A user can send a message through Contact Us. | Realization chapter/page description | Implemented: messages are stored in the database and handled by administrators in the dashboard (Section 11.6); retention period still to be decided |
| UX-01 | Web application is responsive and provides a fluid, usable experience. | Project brief | Explicit quality requirement |

## 4. Role-permission matrix

Permissions below reflect the report as reconciled with the user's confirmed decisions. Server-side enforcement is a project constraint supplied by the user; the authentication boundary, role checks, and marketplace ownership policies (requests, offers, photos, reviews) are implemented and feature-tested.

| Capability | Homeowner | Handyman | Administrator |
|---|---|---|---|
| Register self | Yes; email verification required | Yes; email verification required | No public self-registration; trusted CLI provisioning |
| Log in / log out / password reset | Yes | Yes | Yes, through separate protected admin sign-in |
| Edit own profile / change password / close account | Yes; closure anonymizes identity and preserves history (implemented in account settings) | Same | Managed through trusted admin process; no public self-registration |
| Set/manage handyman skills | No | Define and manage own skills (implemented) | Manage skills |
| Browse service catalog/categories | Yes | Yes | View/manage catalog |
| Search services by category/service/price | Yes: search, category filter, and an offer-price range filter based on pending and accepted offers (services have no fixed price) | Search/filter matching requests by category/service/city | Search/view records for administration |
| Search/list handymen by rating/location/likes | Yes; favorites are removable | No | No |
| Create service request | Yes, own account | No | No |
| See requests | Own requests, details, and photos | Only matching requests/details/photos permitted by skill rules | Monitor platform requests |
| Submit/track offer | Receive offers on own requests | Submit and track own offers | Monitor platform offers |
| Accept/refuse offer | Yes, only for own request | No | No |
| Accept/decline a request | No | No; submit an offer instead | No |
| Contact through WhatsApp | Only after accepting an offer for that request | WhatsApp contact available to the homeowner after acceptance | No conversation access; no internal chat |
| Manage appointments or payments | No; excluded from v1 | No; excluded from v1 | No |
| Submit rating/review | Yes, only after an accepted offer's request is completed and owned by them | No | Monitor reviews |
| Favorite/like handyman | Yes; add/remove favorite | No | No |
| Manage users/block/unblock | No | No | Yes |
| Manage categories/services/skills | No | No | Yes |
| About Us / Contact Us | Public information page; send a contact message (guests too) | Same | Same |
| Read and handle contact messages | No; cannot see any message, including their own | No | Yes: list, search, filter, view, mark new/read/resolved |

**Authorization rule [Decision]:** Scope private records to their owner and enforce role and ownership checks on the server. Homeowners may access only their own requests/offers/profile and service locations; handymen may access only skill-matched requests and their own offers/profile; only administrators may access the administration area. The role boundary and the marketplace ownership checks are implemented and feature-tested.

## 5. Use-case-to-feature mapping

| Use case / diagram group | Feature mapping | Requirements |
|---|---|---|
| Account / connection | Signup, account activation, login, logout, edit profile, change password, delete account, forgot password | ACC-01–14 |
| Admin: manage app/users | User list/details, block/unblock | ADM-01–02 |
| Admin: manage projects/catalog | Service/category overview and CRUD | ADM-03–05 |
| Admin: skills/reviews | Skill administration and review monitoring | ADM-06–07 |
| Homeowner: consult catalog | Browse categories/services | CAT-01–03 |
| Homeowner: search handyman | List/search by rating, location, likes | CAT-04–05 |
| Homeowner: create request | Select category/service, fill details/date/location, optional photos | REQ-01–04 |
| Handyman: view/search requests | Skill-matched list/details with category/service/location filters | CAT-06–07, REQ-05 |
| Handyman: create offer | Offer details and optional photos | REQ-06–08 |
| Homeowner: receive/choose offer | View offers and accept/refuse | REQ-09–10 |
| Handyman request acceptance/creation | Excluded; handyman submits an offer and homeowner creates the request | REQ-11–13; user-confirmed resolution |
| Hire / direct exchange | WhatsApp contact after acceptance; no internal messaging/media | COM-01–04 |
| Appointment | Excluded from v1 | COM-04–05 |
| Review handyman | Required 1–5 rating and optional comment after verified completion; favorites are separate | REV-01–02 |
| Payment | Excluded from v1 | PAY-01; user-confirmed resolution |
| Public information/contact | About Us and Contact Us message | PUB-01–02 |

## 6. Database entity and relationship proposal

The following is the source-analysis transcription of the report's class diagram. Its unresolved UML observations are retained for traceability; it is not the schema authority. The approved relational schema is specified in Section 11.3 and was created in Stage 1. “Multiplicity” reflects the visible UML where legible.

| Entity | UML attributes shown | Relationship proposal |
|---|---|---|
| `User` | `user_id`, `username`, `fullName`, `password`, `email`, `type`, `CIN`, `phoneNumber` | Base identity for Homeowner and Handyman; report UML uses generalization. Account activation/status needed by workflow, but field is not shown. Never store a raw password [technical security proposal]. |
| `Homeowner` | `homeowner_id`, `address`, `city`, `geolocation` | User subtype/profile; owns requests. UML shows one homeowner to many Demandes. |
| `Handyman` | `handyman_id`, `address`, `city`, `rate`, `maritalStatus`, `birthday`, `picture`, `gender` | User subtype/profile; can have many skills; participates in offers and reviews. Some fields are shown but not justified by a use case. |
| `Admin` | `admin_id`, `address`, `city` | Admin identity/profile; report associates admin with user-management and catalog operations. Whether it inherits from User is visually/semantically unclear; project requirement establishes separate role. |
| `Category` | `category_id`, `categoryName` | One Category to one or more Services in UML. |
| `Service` | `service_id`, `serviceName`, `description` | Belongs to one Category; associated with Demande(s); associated to Skills in UML. The UML line is unclear; approved many-to-many mapping is in Section 11.1. |
| `Skills` | `skill_id`, `skillName`, `description` | Handymen have skills (diagram indicates many-to-many); skill/service association appears many-to-many or unclear from crossing lines. |
| `Demande` (Request) | `demande_id`, `title`, `creationDate`, `startDate` | Belongs to one Homeowner and one Service per visible links; has zero or more Offers. Description/location are required by prose but absent as UML attributes. |
| `Offer` | `offer_id`, `price`, `startDate`, `endDate`, `description` | Linked to a Demande and Handyman; can have optional picture(s), Messages, and at most one Review in diagram. UML cardinalities around Offer/Request are hard to interpret and should be reviewed. |
| `PictureOff` | `pictureOff_id`, `picture` | Picture attachment associated with an Offer and/or Demande; diagram reuses this class/label ambiguously. Consider a generic attachment relation only after confirming semantics. |
| `Reviews` | `review_id`, `comment`, `like`, `dislike`, `rate` | Associated with an Offer; diagram indicates optional review per Offer. Review target and uniqueness policy unspecified. |
| `Messages` | `message_id`, `content`, `document`, `order` | Associated with Offer and Demande in diagram. Participants, conversation ownership, timestamps, and attachment representation are not modeled. |
| `PictureSk` | `pictureSk_id`, `picture` | Related to Skills in diagram; exact cardinality is unclear. |
| Payment | No payment class in class diagram | Payment and methods appear only in use-case/organigram. Do not design payment persistence/integration until scope and workflow are confirmed. |
| Appointment | No appointment class in class diagram | Appointment behavior appears in diagram/methods. Do not finalize entity/constraints until lifecycle is clarified. |

**Relational/API-oriented technical proposals [Proposal]:**

- Represent role-specific information with a stable user identity plus role/profile records (or another equivalent normalized design), so authentication, ownership, and API identity remain consistent for future Flutter clients.
- Use explicit foreign keys for ownership and catalog links; enforce access checks in backend services/policies, not only the UI.
- Store uploaded files as private object/file references with authorization checks rather than embedding image/document bytes in relational rows. This is a storage proposal; report only says pictures/documents can be shared.
- Expose business operations through versioned JSON APIs independent of the website UI, allowing a future Flutter client to reuse the same authentication, validation, and authorization rules.
- Use secure password hashing, activation tokens, and server-side validation as standard implementation safeguards. These choices do not add user-facing features.

## 7. Report contradictions and ambiguities with current resolution

The following list preserves the original report analysis. User-confirmed resolutions and remaining implementation detail are summarized after it; “Open” observations in the report do not override the confirmed decisions.

1. **Who creates requests?** Main brief, homeowner screens, and request sequence assign request creation to homeowners. Handyman prose and the use-case diagram also show handyman request creation.
2. **Offer versus accept/decline request:** Main workflow says handymen submit offers and homeowners accept/refuse. Diagram and prose also show handyman accepting/declining demands. The state transitions and intended distinction are not defined.
3. **Account activation versus 2FA:** The account module calls activation “2FA,” but describes an email activation link, not a second authentication factor.
4. **Messaging channel:** Requirements mention direct exchange after acceptance, phone number, WhatsApp, and an in-app messaging system with documents/pictures. It is unclear which is authoritative/required.
5. **Appointments:** Scheduling, acceptance, decline, and rescheduling appear in diagrams, but are absent from the brief, sequences, class model, and realization pages. No lifecycle, time rules, or ownership semantics are defined.
6. **Payments:** Cash, credit card, and PayPal appear in diagrams, but no payment entity, sequence, provider, fee, refund, or realization evidence appears. The use-case diagram associates payment with Handyman.
7. **Handyman request filtering:** Brief says handymen only see requests matching skills. Actor description says category/service/location filtering is future work, but the use-case diagram shows those searches.
8. **Admin taxonomy:** Brief says categories, services, skills, and review monitoring; actor text stresses services/categories; diagrams also show user blocking. Exact admin permissions for skills/reviews are not detailed.
9. **Admin identity:** Admin is a distinct class in UML; User has a `type` attribute, and the user-provided target requires an Administrator role. Inheritance/storage model is not settled.
10. **Data-model gaps:** Request description/location, account activation state, offer/request statuses, timestamps for messages, appointment details, payment records, and review author/target are not fully represented in UML.
11. **Profile attributes:** UML includes CIN, marital status, birthday, gender, geolocation, picture, rate, and address, but the report does not establish which are required, public, or editable.
12. **Likes/dislikes and rating:** Diagram shows rating plus separate like/dislike actions; no aggregation or validation rules are specified.
13. **Pictures:** `PictureOff` is connected around Request and Offer; `PictureSk` is linked to Skills. Cardinality and upload ownership are uncertain.
14. **Messages:** UML links Messages to both Request and Offer; it does not define parties, whether conversations start before acceptance, or document/picture handling.
15. **Contact Us:** The page allows a user to send a message, but recipient, data retention, and response path are absent.
16. **Account deletion/password reset:** These are listed or diagrammed but have no flow, confirmation, recovery, or data-retention rules.
17. **Offer dates and price:** UML fields are present but currency, date meanings, availability, and editing/cancellation behavior are unspecified.
18. **Geographic matching:** Location/geolocation is shown, but no distance, privacy, or matching rule is defined.

| Report issue | Approved resolution / remaining detail |
|---|---|
| 1. Who creates requests? | Resolved: homeowners only. Handyman-created homeowner requests are excluded from v1. |
| 2. Offer vs accept/decline request | Resolved: handymen submit offers; homeowners accept/refuse offers. |
| 3. Activation vs 2FA | Resolved: email verification activates homeowner and handyman accounts; they do not use 2FA in v1. Administrators must use an authenticator-app second factor (approved later; Section 11.7). |
| 4. Messaging channel | Resolved for the website: WhatsApp only after acceptance; no internal chat or document sharing. The future mobile app adds in-app messaging linked to accepted offers (Section 11.8). |
| 5. Appointments | Resolved: excluded from v1. |
| 6. Payments | Resolved: online and cash payments are excluded from v1. Initial offer currency is MAD. |
| 7. Handyman request filtering | Resolved: filtering is included; results remain restricted by skill/service matching. |
| 8. Admin taxonomy | Resolved at capability level: admin manages users/status, categories, services, and skills, and monitors reviews/activity. Specific moderation and statistic displays are implemented only from persisted data. |
| 9. Admin identity | Resolved: one shared `users` identity table with `admin` role; no public admin registration. |
| 10. Data-model gaps | Resolved by Section 11.3 schema and business rules. Messaging/appointment/payment persistence is excluded. |
| 11. Profile fields | Resolved default: name, username, email, required phone for homeowners and handymen (E.164; cleared on anonymization); homeowner/handyman profile city/address; optional handyman photo. No CIN, birthday, gender, marital status, or geolocation is collected in v1. Service address is separate and private on a request. |
| 12. Likes/dislikes | Resolved: homeowner favorite of a handyman, separate from review; review like/dislike excluded. Rating is 1–5; comment optional. |
| 13. Pictures | Resolved: separate private request/offer photo tables, each keyed by `(storage_disk, storage_path)` and ordered per parent. No chat or document attachments. |
| 14. Messages | Resolved: no internal messages. |
| 15. Contact Us | Resolved for v1: messages are stored in the database and handled by administrators in the dashboard; administrators reply from their own email client. No email, SMS or third-party service is involved. Retention period remains an outstanding decision (Section 11.6). |
| 16. Account deletion/password reset | Resolved: forgot-password included; closure anonymizes and soft-deletes the account while retaining historical foreign keys. Password reset, verification, and authentication reject anonymized users. |
| 17. Offer dates and price | Resolved: offer has positive decimal price in MAD, start date, end date, and description; end date cannot precede start date. Editing/withdrawal restrictions are enforced with offer transitions in its module. |
| 18. Geographic matching | Resolved default: use city/location text filters, not coordinate/distance matching. Exact service location remains private and is exposed only to authorized participants. |

## 8. Documented scope and implementation status

### Confirmed v1 functionality

These are report requirements and/or user-confirmed v1 behavior: responsive website; homeowner/handyman registration and email verification; email/password login, logout, and forgot-password; profile/password management; catalog and filters; homeowner-only requests with private location and optional photos; skill-matched handyman discovery; offers with optional photos; homeowner offer choice; WhatsApp after acceptance; completion confirmation; eligible rating/review; removable homeowner favorites; admin account blocking and platform content/user management; Contact Us messages handled by administrators. Implementation status is enumerated in Section 11.4 and the Contact Us workflow in Section 11.6.

### Report features explicitly excluded from v1 or requiring detail before their later implementation

The report still supplies traceability for 2FA for homeowners and handymen, handyman-created requests/direct request acceptance, internal website messaging/media/documents, appointment scheduling, and cash/card/PayPal payments; each is explicitly excluded from v1. Review like/dislike is excluded in favor of a separate homeowner favorite. Contact Us message retention, admin review moderation beyond monitoring, precise handyman ranking semantics, optional profile updates, and a few page-content details are pending their respective implementation phase. These are not silently treated as shipped functionality.

## 9. Approved v1 implementation scope

This is the approved scope, based on the report and the user's confirmed decisions. Items 1–8 are implemented; only the Contact Us retention decision (item 8) is outstanding. Administrator access is additionally protected by a private path and a mandatory second factor (Section 11.7). Item 9 is partly done: the UI is responsive and business rules live outside Blade, but no API exists yet. See Section 11.4.

### Baseline for the first website increment

1. Three protected roles and dashboards with backend-enforced authentication/role checks.
2. Homeowner/handyman registration, email verification, login/logout, forgot-password, and controlled administrator provisioning.
3. Role/profile foundation and approved MySQL marketplace schema, including referential, status, photo-key, review, and accepted-offer constraints.
4. Admin user/profile management, block/unblock, category/service/skill management, persisted activity monitoring, and data-derived statistics.
5. Catalog, homeowner service/handyman search, handyman skill matching, and request filters.
6. Homeowner requests/photos, handyman offers/photos and tracking, homeowner comparison/acceptance, and confirmed completion.
7. Authorized post-acceptance WhatsApp, eligible reviews/ratings, and homeowner favorites.
8. About Us and Contact Us. Contact Us messages are stored and handled by administrators in the dashboard (Section 11.6); the retention period is still to be decided.
9. Responsive UI and backend organization that allows a later versioned API/Flutter client to reuse business rules. No mobile app is part of v1; the approved mobile phase is recorded in Section 11.8.

### Explicitly excluded from v1

2FA for homeowners and handymen (administrators require it, Section 11.7); handyman-created homeowner requests and direct request acceptance; internal chat on the website (approved only for the future mobile app, Section 11.8); document sharing; appointment scheduling/rescheduling; online or cash payment; review like/dislike. Add these only after explicit later approval. Do not collect UML-only identity/profile fields unless they are separately approved.

**Scope safeguard:** Exclusions above are explicit v1 decisions, not accidental omissions. Preserve the report analysis so a future approved scope change can be traced.

## 10. Traceability and remaining implementation detail

The following product/schema decisions are already approved and must not be reopened as if undecided: request creator; email verification vs 2FA; v1 feature exclusions; WhatsApp channel; review eligibility and uniqueness; rating/comment shape; favorite semantics; profile-field default; request/offer/completion lifecycle; skill mapping; anonymization and blocked-account behavior; MAD currency; MySQL concurrency guard; and photo storage-key uniqueness.

- Contact Us message retention period and who may delete messages (no automatic deletion exists; see Section 11.6).
- Admin dashboard statistics are implemented from stored records (users by role/status, requests and offers by status, reviews and average rating, skills, accepted offer value, category demand); further metrics only on request.
- Admin review moderation actions beyond monitoring, if later required.
- Resolved: request and offer photos are JPG, PNG or WebP, at most 5 MB each and 5 per request or offer, stored on the private `local` disk. Handyman profile photos are at most 2 MB.
- Handyman discovery filters by skill and city and orders by average review rating; any more precise ranking rule is still to be defined. The catalog offer-price filter is implemented (Section 11.4, Stage 3).
- API token/session strategy and API contract before exposing a Flutter-facing API.

These implementation details do not change the approved schema or v1 feature scope. No additional features should be inferred from them.

---

# 11. Final approved product specification, schema, and delivery status

**Authority:** This section records the user's confirmed product requirements, approval of the six database defaults and schema, and implementation status. If an earlier analysis section labels a point open or proposed, this section is authoritative for v1. Earlier sections remain to preserve source-to-design traceability.

## 11.1 Six approved database decisions and confirmed product rules

| Decision | Approved default and business rule |
|---|---|
| 1. Offer lifecycle and acceptance | Offer states are `pending`, `accepted`, `declined`, `withdrawn`, and `superseded`. Each request can have at most one accepted offer. Acceptance runs in a transaction while locking the request row; the selected offer becomes accepted, the request points to it, and other pending offers are superseded. A stored generated `accepted_request_id` and unique index provide a MySQL integrity guard. |
| 2. Completion and review lifecycle | Request states are `open`, `booked`, `completion_pending`, `completed`, and `cancelled`. The handyman records completion first, setting `handyman_completed_at` and moving the request to `completion_pending`. It becomes `completed` only after homeowner confirmation, setting `homeowner_confirmed_at`. A review is allowed only when its offer is accepted, its request is completed, the homeowner owns the request, and no review exists. Enforce this operation in a transaction and server-side authorization; the database also enforces one review per offer. |
| 3. Request lifecycle | Only homeowners create requests. A request starts open. Cancellation is available only before offer acceptance; after booking it cannot be deleted or cancelled through the v1 lifecycle. Historical rows are retained. Edits are limited to an open request; exact UI/validation rules are implemented with the request module. |
| 4. Profile fields and privacy | Collect shared first/last name, unique username, unique email, phone (required for homeowners and handymen, validated as E.164; the column stays nullable so anonymization can clear it); homeowner and handyman profile city/address; optional handyman photo. Do not collect CIN, birthday, gender, marital status, or geolocation in v1. Request service location/city is a separate private field, not the profile address. |
| 5. Service-to-skill mapping | A service may map to multiple skills and a handyman may hold multiple skills. Both mappings are many-to-many. Handyman request visibility is filtered server-side using the mapping and approved location/category/service filters. |
| 6. Account deletion and blocking | Users are not physically deleted from historical records. Closing an account anonymizes it: preserve user ID and foreign-key history, set email and username to NULL, set status `anonymized`, and set `deleted_at`. Active and blocked identities require email and username. Blocking records `blocked_at` and `blocked_by`; blocked users cannot use protected marketplace actions. |
| Currency | Offer price is a positive `DECIMAL(12,2)`; currency defaults to `MAD` (Moroccan dirham). Price filtering uses offer prices; services have no fixed-price or homeowner-budget field. |
| Reviews and favorites | Reviews contain `offer_id`, required rating 1–5, optional comment, and timestamps; `offer_id` is the primary key. A homeowner favorite is a separate removable homeowner/handyman relationship. Review likes/dislikes are excluded. |
| Communication and exclusions | WhatsApp is the only communication channel and becomes available to the homeowner only after acceptance. On the website: no internal chat, document sharing, appointments/rescheduling, payments, or handyman-created requests in v1, and no 2FA for homeowners or handymen. In-app messaging is approved for the future mobile app only (Section 11.8). |
| Administrator | Administrator is the `admin` role in the shared users table, provisioned through a trusted CLI command; no public admin signup or public link. Administration is served under a private path and requires a password plus an authenticator-app code (Section 11.7). The protected dashboard manages users, catalog and skills, and monitors persisted requests, offers, reviews, and activity. |

## 11.2 Approved relational schema

All domain tables use InnoDB. IDs and corresponding foreign keys are unsigned and type-matched. Unless explicitly marked NULL, listed columns are NOT NULL. Domain/auth event times use `DATETIME(6)` and follow the application timezone (UTC by default). Profile extension rows intentionally have no independent timestamps because their lifecycle follows the one-to-one user record.

### Reconciled differences and implementation boundary

- The already-executed initial migrations used MySQL `TIMESTAMP(0)` for domain/auth event columns. A forward migration was added and applied to convert all 27 such columns to `DATETIME(6)`; executed migration files were not edited to pretend existing databases had changed.
- The initial photo migrations made `storage_path` globally unique. The forward migration replaced that constraint with the approved `(storage_disk,storage_path)` key for both photo tables. Parent/order uniqueness is retained.
- `reviews.comment` is nullable intentionally. A rating can be submitted without written text. `offer_id` is the primary key and provides the one-review-per-offer uniqueness rule.
- Role-to-profile consistency is maintained by the registration transaction: homeowners get homeowner profiles, handymen get handyman profiles, administrators have neither profile. MySQL foreign keys enforce profile ownership, but do not use a cross-table CHECK to enforce the role/profile pairing.
- Profile rows intentionally omit their own created/updated timestamps; profile state follows the user record. Laravel cache/queue tables retain framework defaults. The framework sessions table keeps its nullable indexed `user_id` without a foreign key. These are intentional infrastructure/modeling choices, not missing domain relationships.
- Self-service account closure and administrator anonymization are implemented on this schema. Accepted-offer locking, superseding of other offers, and review-eligibility transactions are active marketplace workflows, covered by feature tests.

| Table | Columns and nullability |
|---|---|
| `users` | `id` BIGINT UNSIGNED PK; `role` VARCHAR(20) NOT NULL DEFAULT `homeowner`; `first_name`, `last_name` VARCHAR(100) NOT NULL; `username` VARCHAR(40) NULL UNIQUE; `email` VARCHAR(254) NULL UNIQUE; `phone_e164` VARCHAR(16) NULL; `email_verified_at` DATETIME(6) NULL; `password` VARCHAR(255) NOT NULL (hashed); `status` VARCHAR(20) NOT NULL DEFAULT `active`; `blocked_at` DATETIME(6) NULL; `blocked_by` BIGINT UNSIGNED NULL; `remember_token` VARCHAR(100) NULL; `created_at`, `updated_at`, `deleted_at` DATETIME(6) NULL. Indexes: unique username/email, `(role,status)`, `blocked_at`. CHECKs restrict role and enforce identity/status and block-state consistency. |
| `homeowner_profiles` | `user_id` BIGINT UNSIGNED PK/FK; `address` VARCHAR(255) NULL; `city` VARCHAR(120) NULL. |
| `handyman_profiles` | `user_id` BIGINT UNSIGNED PK/FK; `address` VARCHAR(255) NULL; `city` VARCHAR(120) NULL; `photo_path` VARCHAR(512) NULL. Index on city. |
| `categories` | `id` BIGINT UNSIGNED PK; `name` VARCHAR(120) NOT NULL UNIQUE; `is_active` BOOLEAN NOT NULL DEFAULT true; nullable `created_at`/`updated_at` DATETIME(6). |
| `services` | `id` BIGINT UNSIGNED PK; `category_id` BIGINT UNSIGNED NOT NULL; `name` VARCHAR(160) NOT NULL; `description` TEXT NOT NULL; `is_active` BOOLEAN NOT NULL DEFAULT true; nullable `created_at`/`updated_at` DATETIME(6). Unique `(category_id,name)`; index `(category_id,is_active)`. |
| `skills` | `id` BIGINT UNSIGNED PK; `name` VARCHAR(120) NOT NULL UNIQUE; `description` TEXT NULL; `is_active` BOOLEAN NOT NULL DEFAULT true; nullable `created_at`/`updated_at` DATETIME(6). |
| `handyman_skill` | `handyman_id`, `skill_id` BIGINT UNSIGNED NOT NULL; composite PK; `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6); reverse index `(skill_id,handyman_id)`. |
| `service_skill` | `service_id`, `skill_id` BIGINT UNSIGNED NOT NULL; composite PK; reverse index `(skill_id,service_id)`. |
| `service_requests` | `id` BIGINT UNSIGNED PK; `homeowner_id`, `service_id` BIGINT UNSIGNED NOT NULL; `title` VARCHAR(180), `description` TEXT, `service_location` VARCHAR(500), `service_city` VARCHAR(120) NOT NULL; `desired_start_date` DATE NOT NULL; `status` VARCHAR(24) NOT NULL DEFAULT `open`; `accepted_offer_id` BIGINT UNSIGNED NULL; `handyman_completed_at`, `homeowner_confirmed_at`, `cancelled_at` DATETIME(6) NULL; nullable `created_at`/`updated_at` DATETIME(6). Indexes support owner/status/date, service/status/date, city/status, selected-offer lookup. CHECKs enforce valid status/timestamp combinations. |
| `offers` | `id` BIGINT UNSIGNED PK; `request_id`, `handyman_id` BIGINT UNSIGNED NOT NULL; `price` DECIMAL(12,2) NOT NULL; `currency` CHAR(3) NOT NULL DEFAULT `MAD`; `start_date`, `end_date` DATE NOT NULL; `description` TEXT NOT NULL; `status` VARCHAR(20) NOT NULL DEFAULT `pending`; `accepted_at` DATETIME(6) NULL; stored generated `accepted_request_id` BIGINT UNSIGNED NULL, equal to `request_id` only for accepted offers; nullable `created_at`/`updated_at` DATETIME(6). Unique `(request_id,id)` and generated `accepted_request_id`; indexes support request/status, handyman/status/date, currency/price. CHECKs enforce status, positive price, ordered dates, accepted timestamp. |
| `request_photos` | `id` BIGINT UNSIGNED PK; `request_id` BIGINT UNSIGNED NOT NULL; `storage_disk` VARCHAR(50), `storage_path` VARCHAR(512) NOT NULL; `original_name` VARCHAR(255) NULL; `mime_type` VARCHAR(100), `size_bytes` BIGINT UNSIGNED, `sort_order` SMALLINT UNSIGNED NOT NULL; `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6). Unique `(storage_disk,storage_path)` and `(request_id,sort_order)`. |
| `offer_photos` | Same file metadata and key rules as request photos, with `offer_id` as parent and unique `(offer_id,sort_order)`. |
| `reviews` | `offer_id` BIGINT UNSIGNED PK; `rating` TINYINT UNSIGNED NOT NULL CHECK 1–5; `comment` TEXT NULL; nullable `created_at`/`updated_at` DATETIME(6). |
| `handyman_favorites` | `homeowner_id`, `handyman_id` BIGINT UNSIGNED NOT NULL; composite PK; `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6); reverse index `(handyman_id,homeowner_id)`. |
| `activity_events` | `id` BIGINT UNSIGNED PK; `actor_user_id` BIGINT UNSIGNED NULL; `action` VARCHAR(100), `subject_type` VARCHAR(80), `subject_id` BIGINT UNSIGNED NULL; `metadata` JSON NULL; `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6). Indexes support chronology, actor chronology, and subject chronology. Subject type/id are polymorphic and have no subject FK. |
| `contact_messages` | `id` BIGINT UNSIGNED PK; `name` VARCHAR(100), `email` VARCHAR(254), `subject` VARCHAR(150), `message` TEXT NOT NULL; `status` VARCHAR(20) NOT NULL DEFAULT `new`; `read_at`, `resolved_at` DATETIME(6) NULL; `handled_by` BIGINT UNSIGNED NULL FK to `users.id` (SET NULL on delete, CASCADE on update); nullable `created_at`/`updated_at` DATETIME(6). Indexes `(status,created_at)` and `created_at`. CHECKs restrict status to `new`/`read`/`resolved`, require `resolved_at` exactly when resolved, and require `read_at` unless new. No IP address, user agent or account link is stored. Added by a forward-only migration with no `down()`. |

### Relationships, indexes, and deletion behavior

- User has one homeowner or handyman profile depending on role; administrator has no separate profile table. Profile `user_id` is a PK and FK to `users.id`.
- Category has many services. A service belongs to one category and can map to many skills. A handyman can map to many skills.
- Homeowner owns many requests. A request belongs to one service and can have photos and offers. An offer belongs to one request and one handyman and can have photos and one review.
- Composite FK `service_requests(id,accepted_offer_id)` references `offers(request_id,id)`, preventing selection of another request's offer.
- Historical marketplace/profile/catalog relationships use RESTRICT on physical deletion. Users are anonymized/soft-deleted rather than physically removed. `activity_events.actor_user_id` uses SET NULL if physical actor deletion is ever permitted. Referenced IDs are immutable; selected profile/identity relations specify update cascade.
- Photo storage identity is `(storage_disk,storage_path)`, not path alone; the same path may exist on separate disks. A unique parent/sort-order key prevents duplicate ordering slots.
- No appointment, payment, chat, document, or handyman-request tables exist in v1. Password-reset tokens and sessions support authentication. Laravel cache/queue infrastructure tables remain stock framework tables and are outside the domain schema.
- The corrective migration changes every BreIn domain/auth temporal column to `DATETIME(6)`. Framework cache/job tables retain Laravel defaults; the sessions table retains the framework's nullable indexed `user_id` without a foreign key. These are intentional infrastructure differences, not omissions from domain relationships.
- Review `comment` is nullable by design so a rating may be submitted without text. Review `offer_id` is a primary key (therefore unique), rather than a separate surrogate ID plus unique index.

### MySQL acceptance and review integrity

MySQL 8.4.9 is the configured project database. The stored generated column, its unique index, and the composite selected-offer FK have been applied and tested against that server. The generated unique value rejects a second accepted offer for one request. Acceptance must additionally lock the request row and commit request/offer changes in one transaction.

Review creation must lock/check the accepted offer and associated request in one transaction, verify accepted status, completed request, homeowner ownership, and absence of an existing review, then insert. The `reviews.offer_id` PK is the duplicate guard. Comment is intentionally optional; rating is required.

## 11.3 Approved architecture and traceability

- **Website:** Laravel/PHP, Blade, Bootstrap, HTML, CSS, and JavaScript. Responsive role-specific experiences.
- **Database:** MySQL/InnoDB migrations, foreign keys, checks, unique constraints, and indexes.
- **Authorization:** backend role, ownership, account-status, offer-participant, and skill-match checks; UI visibility alone is not authorization.
- **Reusable logic:** keep state transitions/ownership out of Blade so future versioned JSON API endpoints and Flutter clients can reuse them. No marketplace API or mobile app has been built in Stage 1.
- **File privacy:** store photo bytes on private storage; persist disk/path and metadata; authorize every read/write at the request or offer boundary.
- **Traceability:** Section 3 IDs map report requirements to outcomes; Section 5 maps use-case groups; Section 6 preserves UML extraction; Section 7 records contradictions/resolutions; Section 11.2 records role permissions; this section is final schema authority.

| Approved feature | Original report trace | Implementation state |
|---|---|---|
| Signup, activation, login/logout, forgot password | Account module, signup/login pages, use-case diagram | Implemented and feature-tested in Stage 1 |
| Three protected roles and dashboards | Actors, account/user diagrams, confirmed requirements | Implemented; role isolation tested |
| Admin provisioning | Admin actor and protected administration requirement | CLI command implemented and tested through dashboard access |
| Profile fields and role records | User/Homeowner/Handyman UML | Implemented: profile, email re-verification, password, handyman skills and photo, account closure |
| Catalog, services, categories, skills | Service/Category/Skills UML and admin use cases | Implemented: public catalog and admin CRUD with dependency-checked deletion or deactivation |
| Requests, private location, optional photos | Demande/PictureOff UML; homeowner sequence | Implemented: create, edit and cancel while open; private location; private photos |
| Offers, currency, photos, acceptance | Offer UML and offer sequence | Implemented: submit, edit and withdraw; photos; accept or decline under row lock; other offers superseded |
| Completion confirmation and review eligibility | Confirmed completion/review lifecycle | Implemented: handyman completion, homeowner confirmation, one review per eligible offer |
| Favorites, WhatsApp, search, admin oversight | Use cases and confirmed scope | Implemented: favorites, WhatsApp after acceptance, catalog search with offer-price filter, handyman and request search, admin monitoring and activity log |
| Contact Us messages and admin handling | PUB-02, Contact Us page | Implemented and feature-tested (Section 11.6); retention decision outstanding |
| Excluded v1 features | Report diagrams, narrowed by user decisions | Not implemented and must remain excluded |

## 11.4 Stage status and remaining development phases

### Stage 1 — Foundation, approved schema, and authentication: complete

- Laravel 13/PHP backend, MySQL 8.4.9 configuration, Bootstrap/Vite frontend foundation.
- Approved schema migrations applied to development and testing databases.
- Three roles, homeowner/handyman registration, email verification, role-specific login, logout, forgot-password/reset, protected dashboards, active-account enforcement, and CLI administrator provisioning.
- Account identity rules are enforced by DB constraints. Mail defaults to the `log` transport; SMTP delivery (for example Gmail with an App Password) is supported through `.env` and documented in the README.
- Corrective consistency migration applied: domain/auth timestamps are DATETIME(6); request/offer photo uniqueness is disk-scoped.
- MySQL generated-column/unique-index/composite-FK compatibility and authentication paths have MySQL-backed tests.

### Stage 2 — Account and administrator operations: complete

- Owner profile, email re-verification, password change, handyman skills and profile photo, and self-service account closure by anonymization (refused while a booked job is unfinished).
- Administrator user search and details, profile edit, block/unblock, and anonymization; administrator accounts cannot be blocked.
- Category, service, and skill CRUD with server-side dependency reports. A record is physically deleted only when nothing depends on it; otherwise it is deactivated. Dependencies are re-checked under row locks.
- Persisted activity monitor and an admin dashboard of statistics derived from stored records.
- Phone required for homeowners and handymen (E.164, Moroccan local input normalised); 8-character password minimum; branded verification and reset emails; `app:mail-test` diagnostic command.

### Stage 3 — Catalog and discovery: complete

- Public catalog with search and category filter; inactive categories and services leave the catalog. Service pages show offer price statistics.
- Handyman directory filtered by skill and city and ordered by average rating; homeowners add and remove favorites.
- Skill-matched handyman request browser filtered by category, service, city, and keyword.
- Offer-price range filter (MAD). It keeps services with at least one pending or accepted offer in the range, using the same definition as the service page's price range. Withdrawn, declined and superseded offers are ignored, and services without offers are hidden only while a price filter is active. Each card shows its offer range. Invalid ranges return to the catalog with an explanation.

### Stage 4 — Requests and offers: complete

- Homeowner requests with private service location and up to 5 private photos; edit and cancel only while open (cancelling declines pending offers).
- Handyman offers with photos (one active offer per request), edit and withdraw while pending; refused or invalid offers show a visible explanation.
- Homeowner accept or decline. Acceptance locks the request, books it, and supersedes other pending offers; MySQL's generated-column guard rejects a second accepted offer.
- Photos are served only to authorised participants.

### Stage 5 — Completion and feedback: complete

- Handyman marks the job completed; the homeowner confirms completion; one review (rating 1–5, optional comment) per eligible accepted offer.
- WhatsApp contact appears only to the homeowner after acceptance.

### Stage 6 — Public pages, API readiness, and release checks: in progress

- **Done:** About Us page; Contact Us messages with administrator handling (Section 11.6); private administrator access with mandatory second factor (Section 11.7).
- **Not started:** responsive, accessibility, and security release review; reusable versioned API design (no API routes exist yet); deployment readiness. The Flutter mobile app is a later, approved phase (Section 11.8).

Every implemented module is covered by feature tests (`tests/Feature`). Tests that reset the database run only against a `*_testing` database; the others use transactions and can run against the development database.

## 11.5 Remaining implementation detail

These details do not reopen the approved schema or v1 scope:

- Contact Us message retention period and deletion responsibility (outstanding; see Section 11.6).
- Review moderation actions beyond monitoring.
- Any handyman ranking rule beyond ordering by average rating.
- Exact profile/address visibility screens within the approved privacy boundary.
- Versioned API contract, mobile authentication (token) strategy, messaging data model and push-notification provider for the Flutter phase (Section 11.8).
- Production SMTP/provider credentials and deployment configuration.

## 11.6 Contact Us workflow

**Status:** Implemented and feature-tested (`tests/Feature/ContactMessageTest.php`). Messages are stored in the database and handled only through the administrator dashboard. No email, SMTP, SMS or third-party messaging service is used.

### Public form (`GET /contact`, `POST /contact`)

- Anyone, signed in or not, can send a message with four required fields: name (max 100 characters), email (valid RFC address, max 254), subject (3–150) and message (10–5,000). Input is trimmed, internal whitespace in name/subject is collapsed, and the email is lower-cased before validation.
- Validation runs on the server; each rule has a plain-language error shown next to its field, and the visitor's input is kept.
- After a successful submission the form is replaced by a confirmation panel. No acknowledgement email is sent.
- **Spam and repetition protection:**
  - **Rate limits, two layers:**
    - Accepted submissions: 3 per minute and 10 per day per IP address. Only submissions that pass validation count, so correcting a typo never uses up the quota.
    - All requests: the named `contact` throttle allows at most 10 requests per minute per IP, valid or not, to stop raw floods before validation.
    - A throttled visitor is sent back to the form with their text and a clear explanation, not an error page.
    - The IP address is used only by the rate limiter's cache and is never stored with the message.
  - **Honeypot:** a hidden `website` field that people never see. If it is filled in, the visitor sees the normal confirmation but nothing is stored.
  - **Duplicates:** an identical message (same email, subject and text) sent again within 10 minutes is not stored a second time.
- **Data minimisation:** only name, email, subject, message, status and handling timestamps are stored. There is no IP address, user agent, phone number or link to a user account. The page asks visitors not to include passwords or payment details.

### Administrator handling (Admin → Contact messages)

- Only the `admin` role can reach the section, under the private administration path, after password and second factor (`auth`, `active`, `verified`, `admin.2fa` and `role:admin` middleware; Section 11.7). Guests are redirected to the admin sign-in; homeowners and handymen receive 403 and cannot see any message, including their own.
- The list is newest first, with received date and time, sender, subject, excerpt and status. It has counts per status, a status filter, and a search across name, email, subject and message text.
- The detail page shows the full message (escaped, line breaks kept), the received, read and resolved times, and the administrator who last changed the status. Opening a message does not change its status.
- **Statuses:** `new` → `read` → `resolved`. Administrators can also mark a message as resolved directly, reopen it (back to `read`) or mark it unread (back to `new`). `read_at`/`resolved_at` follow the status, and database CHECK constraints keep them consistent.
- Each status change is recorded in the activity monitor as `contact_message.status_updated`, with only the old and new status as metadata.
- Administrators reply from their own email client using the "Reply from your email app" link. BreIn sends nothing itself.
- The admin sidebar shows the number of new messages.

### Outstanding decision: retention

No automatic deletion or deletion screen exists. Messages are kept until a retention policy is approved. Still to be decided:

- How long new, read and resolved messages are kept.
- Who may delete messages, and whether deletion is manual or scheduled.
- Whether resolved messages should be anonymised rather than deleted.

### Outstanding: real support email address

The address shown on the Contact page, `contact@brein.example`, is a **placeholder, not a working mailbox**. It is marked with a `PLACEHOLDER` comment in `resources/views/pages/contact.blade.php` and must be replaced when the owner provides the real support address.

## 11.7 Private administrator access and second factor

**Status:** Implemented and feature-tested (`tests/Feature/AdministratorAccessTest.php`). Approved on 10 November 2026: hide the administrator sign-in from the public website and require a second factor for administrators.

### Private path

- Every administration URL, including the sign-in and second-factor pages, is served under a secret prefix read from `ADMIN_PATH` in the local `.env`. The value is never committed, linked, listed in a sitemap or `robots.txt`, or written in documentation.
- `php artisan app:admin-path --generate` writes a random value (`manage-` plus 32 random characters). `php artisan app:admin-path --show` prints the sign-in URL in a trusted terminal. Without `--show`, nothing secret is printed.
- If `ADMIN_PATH` is missing or shorter than 24 characters, a path derived from `APP_KEY` with an HMAC is used. The area therefore never falls back to a predictable address such as `/admin`.
- `/admin/...` URLs return 404. Named routes (`admin.login`, `admin.dashboard`, …) are unchanged.
- The public header, footer and About page contain no administrator link.
- Administration responses send `X-Robots-Tag: noindex, nofollow, noarchive`, `Referrer-Policy: same-origin` and `Cache-Control: no-store`, so the URL is not indexed, cached or leaked to other sites through the Referer header.
- **The private path is not a security control on its own.** Knowing it grants nothing: every route below still requires the checks that follow.

### Sign-in and second factor (authenticator app, TOTP, RFC 6238)

1. The administrator enters their email and password on the private sign-in page. A correct password does **not** sign them in; it only creates a pending state that lasts 10 minutes. Administrators never get a "remember me" cookie.
2. **First sign-in after this change:** the administrator scans a QR code (or types the key) into an authenticator app and confirms a 6-digit code. They then receive 8 one-time recovery codes, shown once.
3. **Later sign-ins:** they enter the current 6-digit code, or one recovery code.
4. Only then is the session created and marked as having passed the second factor. Middleware `admin.2fa` ends any administrator session without that mark, for example one restored from an old cookie. This applies to the administration area and to the shared account pages.

**Credential storage:** secrets live in the `two_factor_credentials` table (one row per administrator, separate from `users`, forward-only migration).
- The secret is encrypted with `APP_KEY`.
- Recovery codes are stored only as SHA-256 hashes inside an encrypted column.
- The last accepted time step is stored, so a code cannot be replayed.

### Access rules (all enforced on the server)

- Every administration route requires: authenticated session, active account, verified email, second factor passed, and the `admin` role.
- These checks run before route-model binding, so a refused request cannot tell whether a record exists.
- **Guests** are redirected to the private sign-in.
- **Homeowners and handymen** receive 403.
- **Blocked or anonymized accounts** are signed out.
- **Administrator credentials** are refused on the homeowner and handyman sign-in forms, and the reverse.

### Brute-force protection

- **Password step:** 5 attempts per minute per email and IP address; 10 per minute and 30 per hour per IP address.
- **Second-factor step:** 10 submissions per minute per IP address. After 5 wrong codes, the pending sign-in is cancelled and the account's second factor is locked for 15 minutes.
- Failed password and code attempts, successful sign-ins, enrolment, recovery-code regeneration and resets are recorded in the activity monitor.

### Recovery procedure (no lock-out)

- **Lost phone:** sign in with a recovery code, then generate new codes under **Security** (requires the password).
- **Lost phone and recovery codes:** from a trusted terminal on the server, run `php artisan app:admin-two-factor reset <admin email>`. At the next sign-in the administrator enters their password and enrols a new authenticator app. `php artisan app:admin-two-factor status` lists each administrator's state.
- **Forgotten private URL:** run `php artisan app:admin-path --show` on the server.

### Remaining limitations

- An administrator who has not enrolled yet can enrol with the password alone at the next sign-in. Each administrator should therefore sign in and enrol promptly; `app:admin-two-factor status` shows who has not.
- Passkeys (WebAuthn) are not implemented. Authenticator apps were chosen because they need no extra browser support or HTTPS-only APIs and work offline.
- Rate limits use the application cache (`database` store locally). In production they need a shared cache, and the server must report the real client IP address behind any proxy.
- The private path appears in the browser history and address bar of anyone who uses it. It is a concealment layer, not a credential, and should be rotated (`--generate --force`) if it is shared by mistake.
- The forgot-password flow is shared with other roles. A password reset alone still cannot open administration without the second factor.

## 11.8 Future phase — Flutter mobile application (approved, not started)

Approved product decisions, recorded for a later phase. No mobile code exists yet, and the website v1 scope above is unchanged.

| Decision | Detail |
|---|---|
| Client | Flutter mobile application. |
| Backend | The existing Laravel backend and its shared business rules (requests, offers, acceptance, completion, reviews, authorization) serve the mobile app through a versioned API. Rules are not duplicated in the app. |
| Experiences | Separate homeowner and handyman mobile experiences. |
| Messaging | In-app messaging between homeowner and handyman, on mobile only. Each conversation is linked to an accepted offer, so only its two participants can take part. |
| Notifications | Push notifications for new messages. |
| Website channel | WhatsApp remains the communication channel on the website. |
| Administration | Administrator management remains primarily on the website. |

**To decide before implementation:** API contract and versioning; mobile authentication (token) strategy; messaging data model, retention and moderation; push-notification provider; behaviour of conversations after completion or cancellation; whether website users can read mobile conversations.
