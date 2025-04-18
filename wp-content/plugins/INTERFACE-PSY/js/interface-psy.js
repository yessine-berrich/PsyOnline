$(document).ready(function () {
    console.log("JSq loaded");

    $(document).on("click", ".view-details", function () {
        console.log("btn clicked");
        let rdvId = $(this).data("id");
        console.log("rdv ID:", rdvId);

        $.ajax({
            url: psychologue_ajax.ajax_url,
            type: "POST",
            data: {
                action: "get_patient_dossier",
                user_id: rdvId,
                nonce: psychologue_ajax.nonce,
            },
            dataType: "json",
            success: function (data) {
                console.log("AJAX response:", data);
                if (data.success) {
                    let note = data.data;
                    let modal = $(`
                        <dialog id="patient-notes-modal" style="border: none; border-radius: 8px; padding: 20px; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);">
                            <div id="patient-notes">
                                <label for="note">Note générale:</label><br>
                                <textarea id="note" rows="2" cols="50">${note.note || ""}</textarea><br>

                                <label for="antecedents_Medicaux">Antécédents Médicaux:</label><br>
                                <textarea id="antecedents_Medicaux" rows="2" cols="100">${note.antecedents_Medicaux || ""}</textarea><br>

                                <label for="consultations_et_Diagnostiques">Consultations et Diagnostiques:</label><br>
                                <textarea id="consultations_et_Diagnostiques" rows="2" cols="100">${note.consultations_et_Diagnostiques || ""}</textarea><br>

                                <label for="traitements_en_Cours">Traitements en Cours:</label><br>
                                <textarea id="traitements_en_Cours" rows="2" cols="100">${note.traitements_en_Cours || ""}</textarea><br>

                                <label for="examen_et_Resultats">Examen et Résultats:</label><br>
                                <textarea id="examen_et_Resultats" rows="2" cols="100">${note.examen_et_Resultats || ""}</textarea><br>

                                <button id="save-notes" data-rdv-id="${rdvId}">Enregistrer</button>
                                <button id="close-modal">Fermer</button>
                            </div>
                        </dialog>
                    `);

                    $("body").append(modal);
                    modal[0].showModal();
                } else {
                    console.error("Error fetching patient notes:", data);
                    alert("Erreur lors de la récupération des notes du patient");
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX error:", error);
                alert("Erreur lors de la récupération des notes du patient");
            }
        });
    });

    $(document).on("click", "#close-modal", function () {
        $("#patient-notes-modal").remove();
    });

    $(document).on("click", "#save-notes", function () {
        console.log("Save notes clicked");
        let rdvId = $(this).data("rdv-id");
        console.log("rdv ID:", rdvId);

        let noteData = {
            action: "save_patient_dossier",
            user_id: rdvId,
            note: $("#note").val(),
            antecedents_Medicaux: $("#antecedents_Medicaux").val(),
            consultations_et_Diagnostiques: $("#consultations_et_Diagnostiques").val(),
            traitements_en_Cours: $("#traitements_en_Cours").val(),
            examen_et_Resultats: $("#examen_et_Resultats").val(),
            nonce: psychologue_ajax.nonce,
        };

        console.log("Note data to be saved:", noteData);

        $.ajax({
            url: psychologue_ajax.ajax_url,
            type: "POST",
            data: noteData,
            dataType: "json",
            success: function (data) {
                console.log("Save AJAX response:", data);
                if (data.success) {
                    // alert(data.data.message || "Notes enregistrées avec succès");
                    alert(data.data.message);
                    $("#patient-notes-modal").remove();
                } else {
                    console.error("Error saving patient notes:", data.data);
                    alert("Erreur lors de l'enregistrement des notes: " + (data.data.message || "Erreur inconnue"));
                }
            },
            error: function (xhr, status, error) {
                console.error("Save AJAX error:", error);
                alert("Erreur lors de l'enregistrement des notes: " + error);
            }
        });
    });
});
